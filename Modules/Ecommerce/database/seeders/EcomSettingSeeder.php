<?php

namespace Modules\Ecommerce\Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Ecommerce\Models\ShopCoupon;
use Modules\Ecommerce\Models\ShopCurrency;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;

class EcomSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(['tec_key' => 'general'], ['tec_value' => json_encode([
            'name'                           => 'O360 Shop',
            'store_id'                       => mt_rand(1, 3),
            'phone'                          => '010-1234-5678',
            'email'                          => 'contact@orions360.com',
            'logo'                           => '/img/sma-icon.svg',
            'logo_dark'                      => '/img/sma-icon-light.svg',
            'products_per_page'              => '24',
            'shop_mode'                      => 'public',
            'hide_price'                     => '0',
            'disable_cart'                   => '0',
            'guest_checkout'                 => '0',
            'max_unpaid_orders'              => '1',
            'user_registration'              => '1',
            'new_account_email_confirmation' => '1',
            'captcha'                        => 'local',
        ])]);

        Setting::updateOrCreate(['tec_key' => 'seo'], ['tec_value' => json_encode([
            'title'                => 'Shop Home',
            'description'          => 'This is the shop homepage description.',
            'products_title'       => 'Our Products',
            'products_description' => 'Explore our wide range of products available for purchase.',
        ])]);

        Setting::updateOrCreate(['tec_key' => 'shop_slider'], ['tec_value' => json_encode([
            [
                'image'       => '/img/dummy.avif',
                'bg_image'    => '',
                'heading'     => 'Slider Heading 1',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                'button_text' => 'Shop Now',
                'button_link' => url('/shops'),
            ],
            [
                'image'       => '/img/dummy.avif',
                'bg_image'    => '',
                'heading'     => 'Slider Heading 2',
                'description' => 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
                'button_text' => 'Discover More',
                'button_link' => url('/shops'),
            ],
        ])]);
        Setting::updateOrCreate(['tec_key' => 'shop_cta'], ['tec_value' => json_encode([
            'bg_image'    => '/img/cta-bg.avif',
            'heading'     => 'CTA Heading',
            'description' => 'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'button_text' => 'Action',
            'button_link' => url('/shops'),
        ])]);
        Setting::updateOrCreate(['tec_key' => 'shop_footer'], ['tec_value' => json_encode([
            'sections' => [
                ['title' => 'Section 1', 'menus' => [
                    ['label' => 'Section 1 Link 1', 'link' => url('/products')],
                    ['label' => 'Section 1 Link 2', 'link' => url('/products')],
                    ['label' => 'Section 1 Link 3', 'link' => url('/products')],
                    ['label' => 'Section 1 Link 4', 'link' => url('/products')],
                    ['label' => 'Section 1 Link 5', 'link' => url('/products')],
                ]],
                ['title' => 'Section 2', 'menus' => [
                    ['label' => 'Section 2 Link 1', 'link' => url('/products')],
                    ['label' => 'Section 2 Link 2', 'link' => url('/products')],
                    ['label' => 'Section 2 Link 3', 'link' => url('/products')],
                    ['label' => 'Section 2 Link 4', 'link' => url('/products')],
                    ['label' => 'Section 2 Link 5', 'link' => url('/products')],
                    ['label' => 'Section 2 Link 6', 'link' => url('/products')],
                ]],
                ['title' => 'Section 3', 'menus' => [
                    ['label' => 'Section 3 Link 1', 'link' => url('/products')],
                    ['label' => 'Section 3 Link 2', 'link' => url('/products')],
                    ['label' => 'Section 3 Link 3', 'link' => url('/products')],
                ]],
                ['title' => 'Connect', 'menus' => [
                    ['label' => 'Contact Us', 'link' => url('/products')],
                    ['label' => 'Facebook', 'link' => url('/products')],
                    ['label' => 'Instagram', 'link' => url('/products')],
                    ['label' => 'LinkedIn', 'link' => url('/products')],
                ]],
            ],
        ])]);
        Setting::updateOrCreate(['tec_key' => 'page_menus'], ['tec_value' => json_encode([
            ['label' => 'Blog', 'link' => url('page/contact')],
            ['label' => 'FAQ', 'link' => url('page/contact')],
            ['label' => 'About Us', 'link' => url('page/contact')],
            ['label' => 'Contact Us', 'link' => url('page/contact')],
            ['label' => 'Privacy Policy', 'link' => url('page/contact')],
            ['label' => 'Terms of Service', 'link' => url('page/contact')],
        ])]);
        Setting::updateOrCreate(['tec_key' => 'notification'], ['tec_value' => json_encode([
            'message'     => 'Beta release! Shop module is still in pre-release testing!',
            'button_text' => 'Shop Now',
            'button_link' => url('/products'),
        ])]);
        Setting::updateOrCreate(['tec_key' => 'social_links'], ['tec_value' => json_encode([
            'facebook'  => '',
            'instagram' => '',
            'twitter'   => '',
            'linkedin'  => '',
            'youtube'   => '',
            'pinterest' => '',
        ])]);
        Setting::updateOrCreate(['tec_key' => 'newsletter_input'], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'brands_article'], ['tec_value' => '0']);
        Setting::updateOrCreate(['tec_key' => 'payment'], ['tec_value' => json_encode([
            'default_currency' => 'USD',
            'gateway'          => 'Stripe',
            'services'         => [
                'paypal' => [
                    'enabled'   => '1',
                    'client_id' => '',
                    'secret'    => '',
                ],
                'stripe' => [
                    'enabled' => '1',
                    'key'     => '',
                    'secret'  => '',
                ],
            ],
        ])]);

        $usd = Currency::where('code', 'USD')->first();
        ShopCurrency::updateOrCreate(['currency_id' => $usd->id], ['exchange_rate' => 1.00]);
        $eur = Currency::where('code', 'EUR')->first();
        ShopCurrency::updateOrCreate(
            ['currency_id' => $eur->id],
            ['exchange_rate' => 0.9, 'show_at_end' => false, 'auto_update' => true]
        );

//        ShopPage::updateOrCreate(
//            ['slug' => 'about-us'],
//            [
//                'title'       => 'About Us',
//                'description' => 'This is about us page description.',
//                'body'        => 'This is test about us page :)',
//            ]);
//
//        ShopPage::updateOrCreate(
//            ['slug' => 'contact'],
//            [
//                'title'       => 'Contact Us',
//                'description' => 'Get in touch with us for any inquiries or support.',
//                'body'        => '<!-- [map:Menara Kuala Lumpur] -->
//
//<!-- [contact-form] -->',
//            ]
//        );

        ShopCoupon::updateOrCreate(
            ['code' => 'Test5'],
            [
                'discount'    => '5',
                'allowed'     => 1,
                'active'      => 1,
                'expiry_date' => now()->addDays(30),
            ]
        );

        ShippingMethod::updateOrCreate(
            ['provider_name' => 'DHL eCommerce'],
            ['rate' => 8, 'provider_name' => 'DHL eCommerce', 'free_order_amount' => 0]
        );
        $india = Country::where('iso2', 'IN')->first();
        ShippingMethod::updateOrCreate(
            ['provider_name' => 'IndiaPost'],
            [
                'rate'          => 20,
                'provider_name' => 'IndiaPost',
                'country_id'    => $india->id, 'free_order_amount' => 0
            ]
        );
        $malaysia = Country::where('iso2', 'MY')->first();
        ShippingMethod::updateOrCreate(
            ['provider_name' => 'PostLaju'],
            [
                'rate'          => 30,
                'provider_name' => 'PostLaju',
                'country_id'    => $malaysia->id, 'free_order_amount' => 0
            ]
        );

        cache()->flush();
    }
}
