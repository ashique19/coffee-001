<?php

it('redirects guests from admin pages to staff login instead of erroring', function () {
    $this->withoutVite();

    foreach (['/admin/dashboard', '/admin/invoices', '/admin/pos', '/admin/catalog', '/admin/business'] as $path) {
        $this->get($path)
            ->assertRedirect(route('admin.login'))
            ->assertStatus(302);
    }
});
