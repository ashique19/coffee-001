<?php

use App\Models\BusinessSetting;
use Database\Seeders\OwnerAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new OwnerAdminSeeder)->run();
    $this->business = BusinessSetting::singleton();
});

it('shows default our story copy when stored story fields are empty', function () {
    $settings = $this->business->settings;
    data_set($settings, 'content.cafe.about_en', '');
    data_set($settings, 'content.cafe.about_ar', '');
    $this->business->forceFill(['settings' => $settings])->save();

    $pullQuote = __('cafe.default_story.pull_quote', [], 'en');
    $firstParagraph = __('cafe.default_story.paragraphs', [], 'en')[0] ?? '';

    $this->get('/?locale=en')
        ->assertOk()
        ->assertSee($pullQuote, false)
        ->assertSee($firstParagraph, false);
});

it('shows owner story from business settings instead of defaults', function () {
  $custom = 'Our roastery team wrote this custom story for the homepage.';
  $settings = $this->business->settings;
  data_set($settings, 'content.cafe.about_en', $custom);
  $this->business->forceFill(['settings' => $settings])->save();

  $this->get('/?locale=en')
    ->assertOk()
    ->assertSee($custom, false)
    ->assertDontSee(__('cafe.default_story.pull_quote', [], 'en'), false);
});

it('renders default story in arabic rtl locale', function () {
    $settings = $this->business->settings;
    data_set($settings, 'content.cafe.about_en', '');
    data_set($settings, 'content.cafe.about_ar', '');
    $this->business->forceFill(['settings' => $settings])->save();

    $this->get('/?locale=ar')
        ->assertOk()
        ->assertSee(__('cafe.default_story.pull_quote', [], 'ar'), false);
});
