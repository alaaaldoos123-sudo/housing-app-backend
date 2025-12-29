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

        if ($request->filled('province')) {
            $query->where('province', 'like', '%' . $request->province . '%');
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
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

    /**
     * ✅ الدالة الجديدة: جلب عقارات المالك الحالي فقط
     * تسمح للمالك برؤية شققه حتى لو كانت حالتها pending أو rejected
     */
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

    // إضافة شقة جديدة (ترسل للمراجعة)
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string',
            'city'        => 'required|string',
            'province'    => 'required|string',
            'price'       => 'required|numeric',
            'price_unit'  => 'required|string',
            'area'        => 'required|numeric',
            'bedrooms'    => 'required|integer',
            'bathrooms'   => 'required|integer',
            'amenities'   => 'nullable|array',
            'images'      => 'required|array|min:1',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:5120',
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
                'name'          => $request->name,
                'description'   => $request->description,
                'location'      => $request->location,
                'city'          => $request->city,
                'province'      => $request->province,
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
                'message' => 'تم استلام طلب إضافة العقار بنجاح! هو الآن قيد المراجعة من قبل الإدارة، سيتم إشعارك فور نشره.',
                'data'    => new ApartmentResource($apartment)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإضافة: ' . $e->getMessage()
            ], 500);
        }
    }

    // تعديل العقار (يعيده للمراجعة)
    public function update(Request $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا العقار'], 403);
        }

        $request->validate([
            'name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $apartment->fill($request->except(['images']));

        $apartment->status = 'pending';
        $apartment->is_published = false;

        $apartment->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التعديلات. تم إرسال العقار للمراجعة مرة أخرى لضمان جودة المحتوى.',
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
