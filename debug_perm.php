<?php

$user = \App\Models\User::where('email', 'admin@jicos.com')->first();
if (! $user) {
    echo "User not found\n";
    exit;
}

echo 'User: '.$user->name."\n";
echo 'Roles: '.$user->getRoleNames()."\n";
echo "Has 'Super Admin': ".($user->hasRole('Super Admin') ? 'YES' : 'NO')."\n";
echo "Direct Permission 'sales_crm.ai_po_extractor.view': ".($user->hasDirectPermission('sales_crm.ai_po_extractor.view') ? 'YES' : 'NO')."\n";
echo "Effective Permission 'sales_crm.ai_po_extractor.view': ".($user->can('sales_crm.ai_po_extractor.view') ? 'YES' : 'NO')."\n";

// Check if the permission exists in Spatie logic
echo 'Permission Exists in DB: '.(\Spatie\Permission\Models\Permission::where('name', 'sales_crm.ai_po_extractor.view')->exists() ? 'YES' : 'NO')."\n";
