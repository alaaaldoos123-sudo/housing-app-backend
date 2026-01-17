<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Http\Resources\ApartmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Stichoza\GoogleTranslate\GoogleTranslate;

class ApartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Apartment::with('owner')
            ->where('status', 'active')
            ->where('is_published', true);

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

        if ($request->filled('amenities')) {
            $amenities = $request->amenities;

            if (!is_array($amenities)) {
                $amenities = (array)$amenities;
            }

            $query->where(function ($q) use ($amenities) {
                foreach ($amenities as $amenity) {

                    $q->where('amenities', 'like', '%' . $amenity . '%');
                }
            });
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

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'required|string',
            'location'       => 'required|string',
            'city'           => 'required|string',
            'lang'           => 'nullable|string|in:ar,en',
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
            $tr = new GoogleTranslate();
            $inputLang = $request->input('lang', 'ar');

            $nameAr = ""; $nameEn = "";
            $descAr = ""; $descEn = "";
            $locAr = ""; $locEn = "";
            $cityAr = ""; $cityEn = "";

            if ($inputLang == 'ar') {
                $tr->setSource('ar');
                $tr->setTarget('en');

                $nameAr = $request->name;
                $nameEn = $tr->translate($request->name);

                $descAr = $request->description;
                $descEn = $tr->translate($request->description);

                $locAr = $request->location;
                $locEn = $tr->translate($request->location);

                $cityAr = $request->city;
                $cityEn = $tr->translate($request->city);
            } else {
                $tr->setSource('en');
                $tr->setTarget('ar');

                $nameEn = $request->name;
                $nameAr = $tr->translate($request->name);

                $descEn = $request->description;
                $descAr = $tr->translate($request->description);

                $locEn = $request->location;
                $locAr = $tr->translate($request->location);

                $cityEn = $request->city;
                $cityAr = $tr->translate($request->city);
            }

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
                'name_en'       => $nameEn,
                'name_ar'       => $nameAr,
                'description_en'=> $descEn,
                'description_ar'=> $descAr,
                'location_en'   => $locEn,
                'location_ar'   => $locAr,
                'city_en'       => $cityEn,
                'city_ar'       => $cityAr,
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

    public function update(Request $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا العقار'], 403);
        }

        $request->validate([
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'city' => 'nullable|string',
            'lang' => 'nullable|string|in:ar,en',
            'price' => 'nullable|numeric',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $tr = new GoogleTranslate();
        $inputLang = $request->input('lang', 'ar');

        if ($inputLang == 'ar') {
            $tr->setSource('ar'); $tr->setTarget('en');
        } else {
            $tr->setSource('en'); $tr->setTarget('ar');
        }

        if ($request->filled('name')) {
            $apartment->name_ar = $inputLang == 'ar' ? $request->name : $tr->translate($request->name);
            $apartment->name_en = $inputLang == 'en' ? $request->name : $tr->translate($request->name);
        }

        if ($request->filled('description')) {
            $apartment->description_ar = $inputLang == 'ar' ? $request->description : $tr->translate($request->description);
            $apartment->description_en = $inputLang == 'en' ? $request->description : $tr->translate($request->description);
        }

        if ($request->filled('location')) {
            $apartment->location_ar = $inputLang == 'ar' ? $request->location : $tr->translate($request->location);
            $apartment->location_en = $inputLang == 'en' ? $request->location : $tr->translate($request->location);
        }

        if ($request->filled('city')) {
            $apartment->city_ar = $inputLang == 'ar' ? $request->city : $tr->translate($request->city);
            $apartment->city_en = $inputLang == 'en' ? $request->city : $tr->translate($request->city);
        }

        $apartment->fill($request->except(['images', 'name', 'description', 'location', 'city', 'lang']));

        if ($request->hasFile('images')) {
            if ($apartment->image_url) {
                Storage::disk('public')->delete($apartment->image_url);
            }
            if (!empty($apartment->image_urls)) {
                foreach ($apartment->image_urls as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $galleryPaths = [];
            $mainImagePath = null;

            foreach ($request->file('images') as $index => $img) {
                $path = $img->store('apartments', 'public');
                $galleryPaths[] = $path;
                if ($index === 0) {
                    $mainImagePath = $path;
                }
            }

            $apartment->image_url = $mainImagePath;
            $apartment->image_urls = $galleryPaths;
        }

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
