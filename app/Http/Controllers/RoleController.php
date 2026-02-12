<?php

namespace App\Http\Controllers;

use App\Traits\RolesAuthorizable;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\PermissionGroup;
use App\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use RolesAuthorizable;

    /**
     * ============================
     * ROLE INTI (TIDAK BOLEH DIHAPUS)
     * ============================
     */
    protected $protectedRoles = ['pelanggan', 'tukang'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data['roles'] = Role::all();

        return view('role.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->data['action'] = "/role";
        return view('role.form', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRoleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        // 🔒 Ambil field yang diperlukan saja
        Role::create([
            'name' => $request->name,
        ]);

        return redirect('/role')->with('success', 'New role has been created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $this->data['action'] = "/role/showaction/" . $role->id;
        $this->data['permission_groups'] = PermissionGroup::whereNull('permission_group_id')->get();
        $this->data['permissions'] = Permission::whereNull('permission_group_id')->get();

        $this->data['role'] = $role;

        return view('role.permission', $this->data);
    }

    /**
     * Update permission role
     */
    public function showaction(Request $request, Role $role)
    {
        $permission_array = explode(',', $request['permission']);

        // 🔹 revoke permission yang dihapus
        foreach ($role->permissions as $permission) {
            if (!in_array($permission['id'], $permission_array)) {
                $permission = Permission::find($permission['id']);
                $role->revokePermissionTo($permission);
            }
        }

        // 🔹 assign permission baru
        foreach ($permission_array as $permission_id) {
            $permission = Permission::find($permission_id);
            $role->givePermissionTo($permission['name']);
        }

        return redirect('/role')->with('success', 'Permission has been updated!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        // 🚫 role inti tidak boleh diedit namanya
        if (in_array($role->name, $this->protectedRoles)) {
            return redirect('/role')->with('error', 'This role cannot be edited!');
        }

        $this->data['role_data'] = $role;
        $this->data['action'] = "/role/" . $role->id;

        return view('role.form', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRoleRequest  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        // 🚫 role inti tidak boleh diubah
        if (in_array($role->name, $this->protectedRoles)) {
            return redirect('/role')->with('error', 'This role cannot be updated!');
        }

        $role->update([
            'name' => $request->name,
        ]);

        return redirect('/role')->with('success', 'Role has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        // 🚫 role inti tidak boleh dihapus
        if (in_array($role->name, $this->protectedRoles)) {
            return redirect('/role')->with('error', 'This role cannot be deleted!');
        }

        // 🚫 role masih dipakai user
        if ($role->users()->count() > 0) {
            return redirect('/role')
                ->with('error', 'Role is still used by users!');
        }

        Role::destroy($role->id);

        return redirect('/role')->with('success', 'Role has been deleted!');
    }
}
