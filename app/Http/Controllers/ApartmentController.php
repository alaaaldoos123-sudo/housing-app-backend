<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Http\Resources\ApartmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ApartmentController extends Controller
{
    // عرض الشقق للعامة (فقط المقبولة والمنشورة)
    public function index(Request $request)
    {
        $query = Apartment::with('owner')
            ->where('status', 'active')
            ->where('is_published', true);

        // بحث متعدد اللغات
        if ($request->filled('province')) {
            $province = $request->province;
            $query->where(function($q) use ($province) {
                $q->where('province_en', 'like', '%' . $province . '%')
                    ->orWhere('province_ar', 'like', '%' . $province . '%');
            });
        }

        if ($request->filled('city')) {
            $city = $request->city;
            $query->where(function($q) use ($city) {
                $q->where('city_en', 'like', '%' . $city . '%')
                    ->orWhere('city_ar', 'like', '%' . $city . '%');
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('amenities') && is_array($request->amenities)) {
            foreach ($request->amenities as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        $apartments = $query->latest()->get();

        return ApartmentResource::collection($apartments);
    }

    public function myApartments()
    {
        $apartments = Apartment::with('owner')
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();

        return ApartmentResource::collection($apartments);
    }

    public function show($id)
    {
        $apartment = Apartment::with('owner')->findOrFail($id);
        return new ApartmentResource($apartment);
    }

    // إضافة شقة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'name_en'        => 'required|string|max:255',
            'name_ar'        => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'location_en'    => 'nullable|string',
            'location_ar'    => 'nullable|string',
            'city_en'        => 'required|string',
            'city_ar'        => 'required|string',
            'province_en'    => 'required|string',
            'province_ar'    => 'required|string',
            'price'          => 'required|numeric',
            'price_unit'     => 'required|string',
            'area'           => 'required|numeric',
            'bedrooms'       => 'required|integer',
            'bathrooms'      => 'required|integer',
            'amenities'      => 'nullable|array',
            'images'         => 'required|array|min:1',
            'images.*'       => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        try {
            $galleryPaths = [];
            $mainImagePath = null;

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $img) {
                    $path = $img->store('apartments', 'public');
                    $galleryPaths[] = $path;
                    if ($index === 0) {
                        $mainImagePath = $path;
                    }
                }
            }

            $apartment = Apartment::create([
                'owner_id'      => Auth::id(),
                'name_en'       => $request->name_en,
                'name_ar'       => $request->name_ar,
                'description_en'=> $request->description_en,
                'description_ar'=> $request->description_ar,
                'location_en'   => $request->location_en,
                'location_ar'   => $request->location_ar,
                'city_en'       => $request->city_en,
                'city_ar'       => $request->city_ar,
                'province_en'   => $request->province_en,
                'province_ar'   => $request->province_ar,
                'price'         => $request->price,
                'price_unit'    => $request->price_unit,
                'area'          => $request->area,
                'bedrooms'      => $request->bedrooms,
                'bathrooms'     => $request->bathrooms,
                'amenities'     => $request->amenities ?? [],
                'image_url'     => $mainImagePath,
                'image_urls'    => $galleryPaths,
                'status'        => 'pending',
                'is_published'  => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم استلام طلب إضافة العقار بنجاح!',
                'data'    => new ApartmentResource($apartment)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإضافة: ' . $e->getMessage()
            ], 500);
        }
    }

    // تعديل العقار (محدّثة لتدعم الصور ✅)
    public function update(Request $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا العقار'], 403);
        }

        $request->validate([
            'name_en' => 'nullable|string',
            'name_ar' => 'nullable|string',
            'price' => 'nullable|numeric',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'images' => 'nullable|array', // الصور اختيارية عند التعديل
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // 1. تحديث الحقول النصية
        $apartment->fill($request->except(['images']));

        // 2. معالجة الصور (إذا تم رفع صور جديدة)
        if ($request->hasFile('images')) {
            // حذف الصور القديمة من التخزين (اختياري - يفضل لتوفير المساحة)
            if ($apartment->image_url) {
                Storage::disk('public')->delete($apartment->image_url);
            }
            if (!empty($apartment->image_urls)) {
                foreach ($apartment->image_urls as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // رفع الصور الجديدة
            $galleryPaths = [];
            $mainImagePath = null;

            foreach ($request->file('images') as $index => $img) {
                $path = $img->store('apartments', 'public');
                $galleryPaths[] = $path;
                if ($index === 0) {
                    $mainImagePath = $path;
                }
            }

            // تحديث مسارات الصور في الداتابيز
            $apartment->image_url = $mainImagePath;
            $apartment->image_urls = $galleryPaths;
        }

        // 3. إعادة الحالة للمراجعة
        $apartment->status = 'pending';
        $apartment->is_published = false;

        $apartment->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التعديلات وإرسال العقار للمراجعة.',
            'data'    => new ApartmentResource($apartment)
        ]);
    }

    public function destroy($id)
    {
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا العقار'], 403);
        }

        if ($apartment->image_url) {
            Storage::disk('public')->delete($apartment->image_url);
        }
        if (!empty($apartment->image_urls)) {
            foreach ($apartment->image_urls as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $apartment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقار بنجاح'
        ]);
    }
}
