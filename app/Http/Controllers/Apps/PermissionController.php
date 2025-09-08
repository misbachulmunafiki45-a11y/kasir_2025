<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    /**
     * middleware
     *
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['permission:permissions.index'], only: ['index']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get permissions
        $permissions = Permission::when(request()->q, function($permissions) {
            $permissions = $permissions->where('name', 'like', '%'. request()->q . '%');
        })->latest()->paginate(5);

        //return inertia view
        return inertia('Apps/Permissions/Index', [
            'permissions' => $permissions
        ]);
    }
}
