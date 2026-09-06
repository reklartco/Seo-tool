<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Plan matrix from the spec (§9).
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Başlangıç',
                'slug' => 'baslangic',
                'description' => 'Tek siteni takip etmeye başla.',
                'price_monthly' => 490,
                'price_yearly' => 4900,
                'max_projects' => 3,
                'max_keywords' => 20,
                'max_monthly_crawl_pages' => 5000,
                'max_auto_fix_sites' => 0,
                'daily_fix_page_limit' => 0,
                'max_monthly_ai_generations' => 50,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Büyüyen siteler için otomatik düzeltme dahil.',
                'price_monthly' => 1490,
                'price_yearly' => 14900,
                'max_projects' => 10,
                'max_keywords' => 60,
                'max_monthly_crawl_pages' => 30000,
                'max_auto_fix_sites' => 1,
                'daily_fix_page_limit' => 20,
                'max_monthly_ai_generations' => 500,
                'sort_order' => 2,
            ],
            [
                'name' => 'Ajans',
                'slug' => 'ajans',
                'description' => 'Çok sayıda müşteri sitesini tek panelden yönet.',
                'price_monthly' => 3990,
                'price_yearly' => 39900,
                'max_projects' => 30,
                'max_keywords' => 200,
                'max_monthly_crawl_pages' => 150000,
                'max_auto_fix_sites' => 5,
                'daily_fix_page_limit' => 50,
                'max_monthly_ai_generations' => 2000,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
