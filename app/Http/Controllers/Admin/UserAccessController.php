<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:UserRoleManagement');
    }

    public function index()
    {
        $data['data'] = Role::get();
        return view('roles.index', $data);
    }

    public function create_permission()
    {
        $modules = array(
            // 'Language',
            // 'Category',
            // 'CategoryPost',
            // 'Festival',
            // 'FestivalPost',
            // 'CustomCategory',
            // 'CustomFrame',
            // "BusinessCategory",
            // "BusinessSubCategory",
            // "BusinessFrame",
            // "StickerCategory",
            // "Sticker",
            // "ProductCategory",
            // "Product",
            // "Inquiry",
            // "PosterCategory",
            // "PosterMaker",
            // "ReferralSystem",
            // "WithdrawRequest",
            // 'Video',
            // 'News',
            // 'Stories',
            // 'Users',
            // 'Businesses',
            // 'SubscriptionPlan',
            // 'CouponCode',
            // 'BusinessCard',
            // 'FinancialStatistics',
            // 'Entry',
            // 'Subject',
            // 'Notification',
            'WhatsAppMessage',
            // 'Offer',
            // 'UserRoleManagement',
            // 'Settings',
        );

        foreach ($modules as $row) 
        {
            Permission::create(['name' => $row]);
        }

        $all = Permission::all();
        return $all;
    }

    public function create()
    {
        $modules = array(
            'Language',
            'Category',
            'CategoryPost',
            'Festival',
            'FestivalPost',
            'CustomCategory',
            'CustomFrame',
            "BusinessCategory",
            "BusinessSubCategory",
            "BusinessFrame",
            "StickerCategory",
            "Sticker",
            "ProductCategory",
            "Product",
            "Inquiry",
            "PosterCategory",
            "PosterMaker",
            "ReferralSystem",
            "WithdrawRequest",
            'Video',
            'News',
            'Stories',
            'Users',
            'Businesses',
            'SubscriptionPlan',
            'CouponCode',
            'BusinessCard',
            'FinancialStatistics',
            'Entry',
            'Subject',
            'Notification',
            'WhatsAppMessage',
            'Offer',
            'UserRoleManagement',
            'Settings',
        );
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $modules = array(
            'Language',
            'Category',
            'CategoryPost',
            'Festival',
            'FestivalPost',
            'CustomCategory',
            'CustomFrame',
            "BusinessCategory",
            "BusinessSubCategory",
            "BusinessFrame",
            "StickerCategory",
            "Sticker",
            "ProductCategory",
            "Product",
            "Inquiry",
            "PosterCategory",
            "PosterMaker",
            "ReferralSystem",
            "WithdrawRequest",
            'Video',
            'News',
            'Stories',
            'Users',
            'Businesses',
            'SubscriptionPlan',
            'CouponCode',
            'BusinessCard',
            'FinancialStatistics',
            'Entry',
            'Subject',
            'Notification',
            'WhatsAppMessage',
            'Offer',
            'UserRoleManagement',
            'Settings',
        );
        $role = Role::create(['name' => $request->name]);
        foreach ($modules as $row) {
            
            if ($request->$row == 1) {
                $add_perm = Permission::findByName($row);
                $role->givePermissionTo($add_perm);
                $add_perm->assignRole($role);
            }
           
        }
       
        return redirect()->route('roles.index');
    }

    public function edit($id)
    {
        $data['modules'] = array(
            'Language',
            'Category',
            'CategoryPost',
            'Festival',
            'FestivalPost',
            'CustomCategory',
            'CustomFrame',
            "BusinessCategory",
            "BusinessSubCategory",
            "BusinessFrame",
            "StickerCategory",
            "Sticker",
            "ProductCategory",
            "Product",
            "Inquiry",
            "PosterCategory",
            "PosterMaker",
            "ReferralSystem",
            "WithdrawRequest",
            'Video',
            'News',
            'Stories',
            'Users',
            'Businesses',
            'SubscriptionPlan',
            'CouponCode',
            'BusinessCard',
            'FinancialStatistics',
            'Entry',
            'Subject',
            'Notification',
            'WhatsAppMessage',
            'Offer',
            'UserRoleManagement',
            'Settings',
        );
        $data['data'] = Role::find($id);
        return view('roles.edit', $data);
    }

    public function update(Request $request)
    {
        //dd($request->all());
        $role = Role::find($request->id);
        $role->name = $request->name;
        $role->save();

        $modules = array(
            'Language',
            'Category',
            'CategoryPost',
            'Festival',
            'FestivalPost',
            'CustomCategory',
            'CustomFrame',
            "BusinessCategory",
            "BusinessSubCategory",
            "BusinessFrame",
            "StickerCategory",
            "Sticker",
            "ProductCategory",
            "Product",
            "Inquiry",
            "PosterCategory",
            "PosterMaker",
            "ReferralSystem",
            "WithdrawRequest",
            'Video',
            'News',
            'Stories',
            'Users',
            'Businesses',
            'SubscriptionPlan',
            'CouponCode',
            'BusinessCard',
            'FinancialStatistics',
            'Entry',
            'Subject',
            'Notification',
            'WhatsAppMessage',
            'Offer',
            'UserRoleManagement',
            'Settings',
        );
        $all_permissions = array();
        foreach ($modules as $row) 
        {
            if ($request->$row == 1) {
                $all_permissions[] = $row;
                $add_perm = Permission::findByName($row);
                $add_perm->assignRole($role);
            }
        }
        $role->syncPermissions($all_permissions);
        
        //return back();
        return redirect()->route('roles.index');
    }

    public function destroy(Request $request)
    {
        $role = Role::find($request->id);
        foreach ($role->getAllPermissions() as $permission) {
            $role->revokePermissionTo($permission);
            $permission->removeRole($role);
        }
        $role->delete();
        return redirect()->route('roles.index');
    }
}
