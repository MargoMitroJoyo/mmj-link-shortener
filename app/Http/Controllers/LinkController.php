<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $isSignedIn = auth()->check();

        return view('index', compact('isSignedIn'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Redirects to the URL associated with the given slug.
     *
     * @param string $slug The slug of the link.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(string $slug)
    {
        if (!Link::where('slug', $slug)->exists()) {
            return view('errors.404');
        }

        if (!Link::where('slug', $slug)->firstOrFail()->isPublished()) {
            abort(404);
        }

        if (Link::where('slug', $slug)->firstOrFail()->isExpired()) {
            abort(404);
        }

        return redirect()->to(Link::where('slug', $slug)->firstOrFail()->url);
    }
}
