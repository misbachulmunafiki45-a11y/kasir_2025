<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class CategoryController extends Controller implements HasMiddleware
{
    /**
     * middleware
     *
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['permission:categories.index'], only: ['index']),
            new Middleware(['permission:categories.create'], only: ['create', 'store']),
            new Middleware(['permission:categories.edit'], only: ['edit', 'update']),
            new Middleware(['permission:categories.delete'], only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get categories
        $categories = Category::when(request()->q, function($categories) {
            $categories = $categories->where('name', 'like', '%'. request()->q . '%');
        })->latest()->paginate(5);

        //return inertia
        return Inertia::render('Apps/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Apps/Categories/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /**
         * validate
         */
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'name'          => 'required|unique:categories',
            'description'   => 'required'
        ]);

        //upload image
        $image = $request->file('image');
        $image->storeAs('categories', $image->hashName(), 'public');

        //create category
        Category::create([
            'image'         => $image->hashName(),
            'name'          => $request->name,
            'description'   => $request->description
        ]);

        //redirect
        return redirect()->route('apps.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return Inertia::render('Apps/Categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        /**
         * validate
         */
        $request->validate( [
            'name'          => 'required|unique:categories,name,'.$category->id,
            'description'   => 'required'
        ]);

        //check image update
        if ($request->file('image')) {

            //remove old image
            Storage::disk('public')->delete('categories/'.basename($category->image));

            //upload new image
            $image = $request->file('image');
            $image->storeAs('categories', $image->hashName(), 'public');

            //update category with new image
            $category->update([
                'image'=> $image->hashName(),
                'name' => $request->name,
                'description'   => $request->description
            ]);

        }

        //update category without image
        $category->update([
            'name'          => $request->name,
            'description'   => $request->description
        ]);

        //redirect
        return redirect()->route('apps.categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //find by ID
        $category = Category::findOrFail($id);

        //remove image
        Storage::disk('public')->delete('categories/'.basename($category->image));

        //delete
        $category->delete();

        //redirect
        return redirect()->route('apps.categories.index');
    }
}
