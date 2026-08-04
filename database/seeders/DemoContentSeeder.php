<?php

namespace Database\Seeders;

use App\Enums\HostingPlanType;
use App\Enums\PackageCategory;
use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\HostingPlan;
use App\Models\Menu;
use App\Models\Package;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SocialLink;
use App\Models\Solution;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedMenus();
        $this->seedServices();
        $this->seedSolutions();
        $this->seedPortfolio();
        $this->seedPackages();
        $this->seedHostingPlans();
        $this->seedTestimonials();
        $this->seedTeam();
        $this->seedBlog();
        $this->seedFaqs();
        $this->seedPolicyPages();
        $this->seedSocialLinks();

        Settings::flush();
    }

    protected function seedSettings(): void
    {
        $this->seedBrandingAssets();

        Settings::set([
            'company_name' => 'Jamuna Soft',
            'legal_name' => 'Jamuna Soft',
            'site_title' => 'Jamuna Soft — Your Technology & Digital Growth Partner',
            'tagline' => 'Your Technology & Digital Growth Partner',
            'tagline_bn' => 'ব্যবসার প্রযুক্তি, সফটওয়্যার ও ডিজিটাল গ্রোথের বিশ্বস্ত সহযোগী',
            'phone_primary' => '+880 1700-000000',
            'whatsapp_number' => '8801700000000',
            'email_primary' => 'info@jamunasoft.com',
            'email_support' => 'support@jamunasoft.com',
            'office_address' => 'Dhaka, Bangladesh',
            'business_hours' => 'Saturday – Thursday, 10:00 AM – 7:00 PM',
            'header_cta_label' => 'Get a Quotation',
            'header_cta_url' => '/request-a-quotation',
            'footer_text' => 'Jamuna Soft builds websites, custom software, e-commerce platforms and manages hosting, servers and digital marketing for growing businesses in Bangladesh and beyond.',
            'copyright_text' => '© '.date('Y').' Jamuna Soft. All rights reserved.',
            'seo_default_title' => 'Jamuna Soft — Software, Web Development & Hosting in Bangladesh',
            'seo_default_description' => 'Jamuna Soft provides website development, custom software, e-commerce, cloud hosting, server management, and digital marketing services for modern businesses.',
            'contact_form_recipients' => 'info@jamunasoft.com',
            'lead_notification_recipients' => 'info@jamunasoft.com',
        ], 'website');

        Settings::set([
            'hero_heading' => 'We Build Digital Solutions That Grow Your Business',
            'hero_heading_bn' => 'আমরা তৈরি করি ডিজিটাল সমাধান, যা আপনার ব্যবসাকে এগিয়ে নেয়',
            'hero_subheading' => 'Jamuna Soft provides websites, custom software, e-commerce solutions, cloud hosting, server management, digital marketing, creative content, and business automation tailored to modern businesses.',
            'hero_primary_cta_label' => 'Explore Our Services',
            'hero_primary_cta_url' => '/services',
            'hero_secondary_cta_label' => 'Get Free Consultation',
            'hero_secondary_cta_url' => '/contact',
            'hero_badges' => ['Websites', 'Custom Software', 'Hosting', 'Digital Marketing'],
            // Demo statistics — replace with real, verified numbers before launch.
            'stats' => [
                ['value' => '8+', 'label' => 'Years of Experience (demo)'],
                ['value' => '120+', 'label' => 'Completed Projects (demo)'],
                ['value' => '60+', 'label' => 'Active Clients (demo)'],
                ['value' => '80+', 'label' => 'Managed Websites (demo)'],
                ['value' => '25+', 'label' => 'Managed Servers (demo)'],
                ['value' => '99%', 'label' => 'Support Response Rate (demo)'],
            ],
            'why_us' => [
                ['title' => 'Practical industry experience', 'description' => 'We have built and operated real products for education, retail, hospitality and corporate clients.'],
                ['title' => 'Everything in one place', 'description' => 'Development, hosting, maintenance and support under a single roof — one team, one point of contact.'],
                ['title' => 'Custom solutions', 'description' => 'We build around your workflow instead of forcing your business into a template.'],
                ['title' => 'Local business understanding', 'description' => 'We understand how businesses in Bangladesh operate — payments, logistics, language and culture.'],
                ['title' => 'After-sales support', 'description' => 'Launch is the beginning. We stay with you with maintenance plans and fast support.'],
                ['title' => 'Data security & regular backup', 'description' => 'Automated backups, SSL, monitoring and hardening are part of every hosting plan.'],
            ],
            'process_steps' => [
                ['title' => 'Requirement Discussion', 'description' => 'We listen first — your goals, users, and constraints.'],
                ['title' => 'Planning & Quotation', 'description' => 'A clear scope of work, timeline and transparent pricing.'],
                ['title' => 'UI/UX Design', 'description' => 'Wireframes and designs you approve before a line of code.'],
                ['title' => 'Development', 'description' => 'Agile development with regular progress updates.'],
                ['title' => 'Testing', 'description' => 'Functional, device and performance testing before launch.'],
                ['title' => 'Launch', 'description' => 'Deployment, DNS, SSL and go-live checklist handled for you.'],
                ['title' => 'Maintenance & Support', 'description' => 'Updates, backups, monitoring and priority support.'],
            ],
            'cta_heading' => 'Looking for the right digital solution for your business?',
            'cta_heading_bn' => 'আপনার ব্যবসার জন্য সঠিক ডিজিটাল সমাধান খুঁজছেন?',
            'cta_description' => 'Tell us about your project and get a free consultation with a clear, honest quotation — no obligations.',
            'about_intro' => 'Jamuna Soft is a Bangladesh-based technology company providing software development, web solutions, hosting and digital growth services. (Demo copy — edit in admin.)',
            'about_story' => 'Founded by engineers who spent years building products for local and international clients, Jamuna Soft exists to give growing businesses one dependable technology partner — from the first landing page to full business automation. (Demo copy.)',
            'mission' => 'To help businesses grow with dependable, affordable and modern technology.',
            'vision' => 'To become the most trusted digital growth partner for SMEs in Bangladesh.',
            'core_values' => [
                ['title' => 'Honesty', 'description' => 'Transparent scope, pricing and timelines.'],
                ['title' => 'Craftsmanship', 'description' => 'We ship work we are proud of.'],
                ['title' => 'Partnership', 'description' => 'Your growth is our success metric.'],
            ],
            'brand_meaning' => 'Technology that connects ideas, builds solutions, and helps businesses grow.',
            'brand_meaning_bn' => 'প্রযুক্তির মাধ্যমে ধারণাকে বাস্তব সমাধানে রূপ দিয়ে ব্যবসাকে এগিয়ে নেওয়া।',
            'brand_story_intro' => 'Our logo tells the story of who we are — a modern, dependable and forward-moving technology company.',
            'brand_story_intro_bn' => 'লোগোটির মূল অর্থ হলো — Jamuna Soft একটি আধুনিক, নির্ভরযোগ্য ও অগ্রসর প্রযুক্তি প্রতিষ্ঠান।',
            'brand_story_points' => [
                ['title' => 'The “J” mark', 'description' => 'The symbol on the left is the letter “J” — for Jamuna.'],
                ['title' => 'Layered strokes', 'description' => 'The layered lines of the J represent software architecture, digital connection, data flow and continuous improvement.'],
                ['title' => 'The pixels above', 'description' => 'The small squares stand for code, data, cloud and digital innovation.'],
                ['title' => 'The rising form', 'description' => 'The structure climbing from bottom to top expresses business growth, progress and moving toward the future.'],
                ['title' => 'Deep navy', 'description' => 'The deep navy colour symbolises trust, security and professionalism.'],
                ['title' => 'Blue & cyan', 'description' => 'Blue and cyan symbolise technology, innovation, speed and modernity.'],
                ['title' => 'The wordmark', 'description' => '“Jamuna” is set in a deep shade to express the company’s stability, while “Soft” in bright blue highlights technology and creativity.'],
            ],
            'brand_story_points_bn' => [
                ['title' => '“J” প্রতীক', 'description' => 'বাঁ পাশের প্রতীকটি ইংরেজি “J”, যা Jamuna-কে বোঝায়।'],
                ['title' => 'স্তরভিত্তিক রেখা', 'description' => 'J-এর স্তরভিত্তিক রেখাগুলো সফটওয়্যার আর্কিটেকচার, ডিজিটাল কানেকশন, ডেটা ফ্লো ও ধারাবাহিক উন্নতি প্রকাশ করে।'],
                ['title' => 'উপরের পিক্সেল', 'description' => 'উপরের ছোট স্কয়ার/পিক্সেলগুলো কোড, ডেটা, ক্লাউড ও ডিজিটাল ইনোভেশন বোঝায়।'],
                ['title' => 'ঊর্ধ্বমুখী গঠন', 'description' => 'নিচ থেকে উপরের দিকে ওঠা গঠনটি ব্যবসার বৃদ্ধি, অগ্রগতি ও ভবিষ্যতের দিকে এগিয়ে যাওয়া প্রকাশ করে।'],
                ['title' => 'ডিপ নেভি রং', 'description' => 'Deep navy রং বিশ্বাসযোগ্যতা, নিরাপত্তা ও পেশাদারিত্বের প্রতীক।'],
                ['title' => 'ব্লু ও সায়ান রং', 'description' => 'Blue ও cyan রং প্রযুক্তি, নতুনত্ব, গতি ও আধুনিকতার প্রতীক।'],
                ['title' => 'ওয়ার্ডমার্ক', 'description' => '“Jamuna” গাঢ় রঙে রেখে প্রতিষ্ঠানের স্থায়িত্ব বোঝানো হয়েছে, আর “Soft” উজ্জ্বল নীল রঙে রেখে প্রযুক্তি ও সৃজনশীলতাকে গুরুত্ব দেওয়া হয়েছে।'],
            ],
        ], 'content');
    }

    /**
     * Copy the real brand assets from public/assets into managed storage
     * (where the admin panel's file uploads also live) and register them.
     */
    protected function seedBrandingAssets(): void
    {
        $assets = [
            'logo_path' => ['source' => public_path('assets/logo.png'), 'target' => 'branding/logo.png'],
            'favicon_path' => ['source' => public_path('assets/fabicon.webp'), 'target' => 'branding/favicon.webp'],
        ];

        foreach ($assets as $key => $asset) {
            if (! is_file($asset['source'])) {
                continue;
            }

            $disk = Storage::disk('public');

            if (! $disk->exists($asset['target'])) {
                $disk->put($asset['target'], file_get_contents($asset['source']));
            }

            Settings::set([$key => $asset['target']], 'website');
        }
    }

    protected function seedMenus(): void
    {
        $header = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header Menu']);

        if ($header->allItems()->count() === 0) {
            $items = [
                ['label' => 'Home', 'url' => '/', 'bn' => 'হোম'],
                ['label' => 'Services', 'url' => '/services', 'bn' => 'সেবাসমূহ'],
                ['label' => 'Solutions', 'url' => '/solutions', 'bn' => 'সলিউশন'],
                ['label' => 'Portfolio', 'url' => '/portfolio', 'bn' => 'পোর্টফোলিও'],
                ['label' => 'Hosting', 'url' => '/hosting', 'bn' => 'হোস্টিং'],
                ['label' => 'Packages', 'url' => '/packages', 'bn' => 'প্যাকেজ'],
                ['label' => 'About Us', 'url' => '/about', 'bn' => 'আমাদের সম্পর্কে'],
                ['label' => 'Blog', 'url' => '/blog', 'bn' => 'ব্লগ'],
                ['label' => 'Contact', 'url' => '/contact', 'bn' => 'যোগাযোগ'],
            ];

            foreach ($items as $index => $item) {
                $header->allItems()->create([
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'sort_order' => $index,
                    'translations' => ['bn' => ['label' => $item['bn']]],
                ]);
            }
        }

        $company = Menu::firstOrCreate(['location' => 'footer_company'], ['name' => 'Footer — Company']);

        if ($company->allItems()->count() === 0) {
            foreach ([
                ['About Us', '/about'], ['Portfolio', '/portfolio'], ['Blog', '/blog'],
                ['Contact', '/contact'], ['Request a Quotation', '/request-a-quotation'],
            ] as $index => [$label, $url]) {
                $company->allItems()->create(['label' => $label, 'url' => $url, 'sort_order' => $index]);
            }
        }

        $legal = Menu::firstOrCreate(['location' => 'footer_legal'], ['name' => 'Footer — Legal']);

        if ($legal->allItems()->count() === 0) {
            foreach ([
                ['Privacy Policy', '/page/privacy-policy'],
                ['Terms & Conditions', '/page/terms-and-conditions'],
                ['Refund Policy', '/page/refund-policy'],
                ['Cookie Policy', '/page/cookie-policy'],
            ] as $index => [$label, $url]) {
                $legal->allItems()->create(['label' => $label, 'url' => $url, 'sort_order' => $index]);
            }
        }
    }

    protected function seedServices(): void
    {
        $categories = [
            'development' => ServiceCategory::firstOrCreate(['slug' => 'development'], ['name' => 'Development', 'sort_order' => 0]),
            'infrastructure' => ServiceCategory::firstOrCreate(['slug' => 'infrastructure'], ['name' => 'Hosting & Infrastructure', 'sort_order' => 1]),
            'growth' => ServiceCategory::firstOrCreate(['slug' => 'growth'], ['name' => 'Growth & Creative', 'sort_order' => 2]),
        ];

        $services = [
            [
                'category' => 'development', 'name' => 'Website Development', 'slug' => 'website-development',
                'icon' => 'globe-alt', 'featured' => true,
                'excerpt' => 'Fast, modern, mobile-first business websites that convert visitors into customers.',
                'technologies' => ['Laravel', 'PHP', 'Tailwind CSS', 'MySQL', 'Alpine.js'],
                'benefits' => [
                    ['title' => 'SEO-ready from day one', 'description' => 'Clean markup, fast loading and structured data built in.'],
                    ['title' => 'Easy content management', 'description' => 'Update text, images and pages without touching code.'],
                    ['title' => 'Mobile-first design', 'description' => 'Perfect experience on every screen size.'],
                ],
            ],
            [
                'category' => 'development', 'name' => 'Custom Software Development', 'slug' => 'custom-software-development',
                'icon' => 'code-bracket', 'featured' => true,
                'excerpt' => 'ERP, CRM, inventory, billing and workflow software built around exactly how your business runs.',
                'technologies' => ['Laravel', 'Livewire', 'MySQL', 'Redis', 'REST APIs'],
                'benefits' => [
                    ['title' => 'Fits your workflow', 'description' => 'No more forcing your process into off-the-shelf tools.'],
                    ['title' => 'Scales with you', 'description' => 'Modular architecture that grows as your team grows.'],
                    ['title' => 'Your data, your control', 'description' => 'Hosted on your infrastructure with full ownership.'],
                ],
            ],
            [
                'category' => 'development', 'name' => 'E-commerce Development', 'slug' => 'ecommerce-development',
                'icon' => 'shopping-cart', 'featured' => true,
                'excerpt' => 'Complete online stores with payments, delivery integration, inventory and order management.',
                'technologies' => ['Laravel', 'bKash/Nagad/SSLCommerz', 'Pathao/Steadfast APIs', 'MySQL'],
                'benefits' => [
                    ['title' => 'Local payments built in', 'description' => 'bKash, Nagad, cards and cash-on-delivery ready.'],
                    ['title' => 'Delivery integrations', 'description' => 'Courier APIs for automatic booking and tracking.'],
                    ['title' => 'Sales analytics', 'description' => 'Know your best products, customers and campaigns.'],
                ],
            ],
            [
                'category' => 'infrastructure', 'name' => 'Web Hosting & Cloud Services', 'slug' => 'web-hosting',
                'icon' => 'cloud', 'featured' => true,
                'excerpt' => 'Fast SSD hosting, cloud servers, business email and SSL — managed by engineers, not ticket bots.',
                'technologies' => ['Ubuntu', 'Nginx', 'CloudLinux', 'LiteSpeed', 'cPanel'],
                'benefits' => [
                    ['title' => 'Daily backups', 'description' => 'Automatic off-site backups with quick restore.'],
                    ['title' => 'Free SSL', 'description' => 'Every plan ships with HTTPS enabled.'],
                    ['title' => 'Real human support', 'description' => 'Talk to the engineers who run the servers.'],
                ],
            ],
            [
                'category' => 'infrastructure', 'name' => 'Server Management', 'slug' => 'server-management',
                'icon' => 'server-stack', 'featured' => false,
                'excerpt' => 'VPS and dedicated server setup, hardening, monitoring, patching and 24/7 incident response.',
                'technologies' => ['Ubuntu', 'Nginx', 'Docker', 'MySQL/MariaDB', 'UFW/Fail2ban'],
                'benefits' => [
                    ['title' => 'Security hardening', 'description' => 'Firewalls, fail2ban, updates and least-privilege access.'],
                    ['title' => 'Proactive monitoring', 'description' => 'We usually know before you do.'],
                    ['title' => 'Performance tuning', 'description' => 'Databases and web servers tuned for your workload.'],
                ],
            ],
            [
                'category' => 'infrastructure', 'name' => 'Website Maintenance', 'slug' => 'website-maintenance',
                'icon' => 'wrench-screwdriver', 'featured' => false,
                'excerpt' => 'Updates, backups, uptime monitoring, security patching and content changes on a simple monthly plan.',
                'technologies' => ['Laravel', 'WordPress', 'MySQL', 'Cloudflare'],
                'benefits' => [
                    ['title' => 'Always up to date', 'description' => 'Framework, plugin and dependency updates handled.'],
                    ['title' => 'Uptime monitoring', 'description' => 'Instant alerts and fast response when something breaks.'],
                    ['title' => 'Content support', 'description' => 'Monthly content changes included in every plan.'],
                ],
            ],
            [
                'category' => 'growth', 'name' => 'Digital Marketing', 'slug' => 'digital-marketing',
                'icon' => 'megaphone', 'featured' => true,
                'excerpt' => 'SEO, Google Ads, Facebook marketing and content strategy that bring measurable leads — not vanity metrics.',
                'technologies' => ['Google Ads', 'Meta Ads', 'Analytics 4', 'Search Console'],
                'benefits' => [
                    ['title' => 'Measurable results', 'description' => 'Every campaign reports leads and cost per acquisition.'],
                    ['title' => 'Local + global reach', 'description' => 'Campaigns in Bengali and English for the right audience.'],
                    ['title' => 'Full-funnel strategy', 'description' => 'From awareness to remarketing to conversion.'],
                ],
            ],
            [
                'category' => 'growth', 'name' => 'Graphics & Video Content', 'slug' => 'graphics-video-content',
                'icon' => 'paint-brush', 'featured' => false,
                'excerpt' => 'Brand identity, social media creatives, motion graphics and product videos that make you look professional.',
                'technologies' => ['Illustrator', 'Photoshop', 'Premiere Pro', 'After Effects'],
                'benefits' => [
                    ['title' => 'Consistent branding', 'description' => 'One visual language across web, print and social.'],
                    ['title' => 'Social-ready packs', 'description' => 'Monthly creative packages for your pages.'],
                    ['title' => 'Video that sells', 'description' => 'Short-form product and promo videos.'],
                ],
            ],
            [
                'category' => 'growth', 'name' => 'AI & Business Automation', 'slug' => 'ai-automation',
                'icon' => 'cpu-chip', 'featured' => true,
                'excerpt' => 'Chatbots, workflow automation, AI-assisted reporting and integrations that remove repetitive work.',
                'technologies' => ['Claude API', 'Python', 'Laravel', 'Zapier/n8n', 'WhatsApp Business API'],
                'benefits' => [
                    ['title' => 'Save staff hours', 'description' => 'Automate data entry, follow-ups and reports.'],
                    ['title' => '24/7 customer response', 'description' => 'AI chat handles common questions instantly.'],
                    ['title' => 'Connected systems', 'description' => 'Your website, CRM and accounting talking to each other.'],
                ],
            ],
        ];

        foreach ($services as $index => $data) {
            Service::firstOrCreate(['slug' => $data['slug']], [
                'service_category_id' => $categories[$data['category']]->id,
                'name' => $data['name'],
                'excerpt' => $data['excerpt'],
                'description' => '<p>'.$data['excerpt'].'</p><p>This is demo content seeded for preview purposes — replace it with your real service description from the admin panel.</p>',
                'icon' => $data['icon'],
                'benefits' => $data['benefits'],
                'features' => $data['benefits'],
                'technologies' => $data['technologies'],
                'process_steps' => [
                    ['title' => 'Discovery', 'description' => 'We map your requirements and goals.'],
                    ['title' => 'Proposal', 'description' => 'Clear scope, timeline and pricing.'],
                    ['title' => 'Delivery', 'description' => 'Design, build, test and launch.'],
                    ['title' => 'Support', 'description' => 'Ongoing maintenance and improvements.'],
                ],
                'is_featured' => $data['featured'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    protected function seedSolutions(): void
    {
        $solutions = [
            ['name' => 'Education & Online Learning', 'slug' => 'education', 'icon' => 'academic-cap', 'services' => ['website-development', 'custom-software-development', 'web-hosting'],
                'excerpt' => 'School management systems, e-learning platforms, admission portals and result publishing.'],
            ['name' => 'Healthcare & Medical', 'slug' => 'healthcare', 'icon' => 'heart', 'services' => ['website-development', 'custom-software-development'],
                'excerpt' => 'Clinic websites, appointment booking, patient records and diagnostic report delivery.'],
            ['name' => 'E-commerce & Retail', 'slug' => 'ecommerce', 'icon' => 'shopping-bag', 'services' => ['ecommerce-development', 'digital-marketing', 'web-hosting'],
                'excerpt' => 'Online stores, POS integration, inventory and delivery automation for retailers.'],
            ['name' => 'Hotels, Resorts & Restaurants', 'slug' => 'hospitality', 'icon' => 'building-storefront', 'services' => ['website-development', 'digital-marketing', 'graphics-video-content'],
                'excerpt' => 'Booking websites, menu systems, review management and seasonal campaigns.'],
            ['name' => 'Real Estate & Construction', 'slug' => 'real-estate', 'icon' => 'home-modern', 'services' => ['website-development', 'digital-marketing'],
                'excerpt' => 'Project showcases, plot/flat inventory, lead capture and CRM follow-up.'],
            ['name' => 'Engineering & Lift Companies', 'slug' => 'engineering', 'icon' => 'wrench', 'services' => ['website-development', 'custom-software-development', 'website-maintenance'],
                'excerpt' => 'Service catalogs, AMC tracking, maintenance schedules and support ticketing.'],
            ['name' => 'Corporate Organizations', 'slug' => 'corporate-business', 'icon' => 'building-office-2', 'services' => ['website-development', 'custom-software-development', 'server-management'],
                'excerpt' => 'Corporate websites, intranets, HR tools and process automation.'],
            ['name' => 'Daycare & Service Businesses', 'slug' => 'service-business', 'icon' => 'users', 'services' => ['website-development', 'digital-marketing', 'ai-automation'],
                'excerpt' => 'Booking, subscription billing, parent/client communication portals and local SEO.'],
        ];

        foreach ($solutions as $index => $data) {
            $solution = Solution::firstOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'excerpt' => $data['excerpt'],
                'description' => '<p>'.$data['excerpt'].'</p><p>Demo content — describe how Jamuna Soft serves this industry from the admin panel.</p>',
                'icon' => $data['icon'],
                'challenges' => [
                    ['title' => 'Manual processes', 'description' => 'Paper and spreadsheet workflows slow everything down.'],
                    ['title' => 'Weak online presence', 'description' => 'Customers search online first and judge instantly.'],
                    ['title' => 'Scattered data', 'description' => 'Information stuck in phones, notebooks and inboxes.'],
                ],
                'offerings' => [
                    ['title' => 'Purpose-built platform', 'description' => 'Software designed for this industry\'s exact workflow.'],
                    ['title' => 'Professional web presence', 'description' => 'A fast, trustworthy website that converts.'],
                    ['title' => 'Automation & reporting', 'description' => 'Less typing, more insight.'],
                ],
                'benefits' => [
                    ['title' => 'More enquiries', 'description' => 'Be found and be trusted online.'],
                    ['title' => 'Lower admin cost', 'description' => 'Automate repetitive back-office work.'],
                    ['title' => 'Better decisions', 'description' => 'Live dashboards instead of month-old reports.'],
                ],
                'is_featured' => $index < 4,
                'is_active' => true,
                'sort_order' => $index,
            ]);

            $ids = Service::whereIn('slug', $data['services'])->pluck('id');
            $solution->services()->syncWithoutDetaching($ids);
        }
    }

    protected function seedPortfolio(): void
    {
        $categories = [
            'website' => PortfolioCategory::firstOrCreate(['slug' => 'website'], ['name' => 'Website', 'sort_order' => 0]),
            'software' => PortfolioCategory::firstOrCreate(['slug' => 'custom-software'], ['name' => 'Custom Software', 'sort_order' => 1]),
            'ecommerce' => PortfolioCategory::firstOrCreate(['slug' => 'ecommerce'], ['name' => 'E-commerce', 'sort_order' => 2]),
            'education' => PortfolioCategory::firstOrCreate(['slug' => 'education'], ['name' => 'Education', 'sort_order' => 3]),
            'hosting' => PortfolioCategory::firstOrCreate(['slug' => 'hosting'], ['name' => 'Hosting', 'sort_order' => 4]),
            'automation' => PortfolioCategory::firstOrCreate(['slug' => 'business-automation'], ['name' => 'Business Automation', 'sort_order' => 5]),
        ];

        $projects = [
            [
                'category' => 'education', 'title' => 'Online Learning Platform (Demo Case Study)', 'slug' => 'online-learning-platform',
                'industry' => 'Education', 'client' => 'Demo Education Client', 'featured' => true,
                'summary' => 'A complete e-learning platform with course sales, video lessons, quizzes and student progress tracking.',
                'services' => ['website-development', 'custom-software-development', 'web-hosting'],
                'technologies' => ['Laravel', 'MySQL', 'Tailwind CSS', 'VdoCipher'],
            ],
            [
                'category' => 'software', 'title' => 'Corporate ERP System (Demo Case Study)', 'slug' => 'corporate-erp-system',
                'industry' => 'Corporate', 'client' => 'Demo Corporate Client', 'featured' => true,
                'summary' => 'HR, payroll, inventory and accounting modules unified into one ERP with role-based dashboards.',
                'services' => ['custom-software-development', 'server-management'],
                'technologies' => ['Laravel', 'Livewire', 'MariaDB', 'Redis'],
            ],
            [
                'category' => 'website', 'title' => 'Resort & Hospitality Website (Demo Case Study)', 'slug' => 'resort-website',
                'industry' => 'Hospitality', 'client' => 'Demo Resort Client', 'featured' => true,
                'summary' => 'A visually rich resort website with room booking enquiries, gallery and seasonal offer management.',
                'services' => ['website-development', 'digital-marketing'],
                'technologies' => ['Laravel', 'Alpine.js', 'Tailwind CSS'],
            ],
            [
                'category' => 'ecommerce', 'title' => 'Fashion E-commerce Platform (Demo Case Study)', 'slug' => 'fashion-ecommerce-platform',
                'industry' => 'Retail', 'client' => 'Demo Retail Client', 'featured' => true,
                'summary' => 'Multi-category online store with bKash/Nagad payments, courier integration and abandoned-cart recovery.',
                'services' => ['ecommerce-development', 'digital-marketing', 'web-hosting'],
                'technologies' => ['Laravel', 'MySQL', 'SSLCommerz', 'Pathao API'],
            ],
            [
                'category' => 'website', 'title' => 'Service Business Website (Demo Case Study)', 'slug' => 'service-business-website',
                'industry' => 'Services', 'client' => 'Demo Services Client', 'featured' => false,
                'summary' => 'Lead-generation website for a service company with quotation forms, testimonials and local SEO.',
                'services' => ['website-development', 'digital-marketing'],
                'technologies' => ['Laravel', 'Tailwind CSS'],
            ],
            [
                'category' => 'hosting', 'title' => 'Managed Hosting Migration (Demo Case Study)', 'slug' => 'managed-hosting-migration',
                'industry' => 'Infrastructure', 'client' => 'Demo Hosting Client', 'featured' => false,
                'summary' => 'Migration of 20+ business websites to managed cloud infrastructure with zero downtime and daily backups.',
                'services' => ['web-hosting', 'server-management'],
                'technologies' => ['Ubuntu', 'Nginx', 'CloudLinux', 'Cloudflare'],
            ],
        ];

        foreach ($projects as $index => $data) {
            $portfolio = Portfolio::firstOrCreate(['slug' => $data['slug']], [
                'portfolio_category_id' => $categories[$data['category']]->id,
                'title' => $data['title'],
                'client_name' => $data['client'],
                'industry' => $data['industry'],
                'summary' => $data['summary'],
                'challenge' => '<p>Demo content: describe the client\'s starting point and pain points here.</p>',
                'solution' => '<p>Demo content: describe the solution Jamuna Soft delivered here.</p>',
                'key_features' => [
                    ['title' => 'Feature one', 'description' => 'Demo feature description.'],
                    ['title' => 'Feature two', 'description' => 'Demo feature description.'],
                    ['title' => 'Feature three', 'description' => 'Demo feature description.'],
                ],
                'technologies' => $data['technologies'],
                'results' => [
                    ['title' => 'Result metric (demo)', 'description' => 'Replace with a real, verified outcome.'],
                ],
                'completed_at' => now()->subMonths($index + 2),
                'is_featured' => $data['featured'],
                'is_active' => true,
                'sort_order' => $index,
            ]);

            $ids = Service::whereIn('slug', $data['services'])->pluck('id');
            $portfolio->services()->syncWithoutDetaching($ids);
        }
    }

    protected function seedPackages(): void
    {
        $packages = [
            ['name' => 'Starter Business Website', 'slug' => 'starter-business-website', 'category' => PackageCategory::Website,
                'price' => 15000, 'suffix' => 'one-time', 'delivery' => '7–10 days', 'support' => '3 months free support', 'recommended' => false, 'featured' => true,
                'features' => ['Up to 5 pages', 'Mobile-responsive design', 'Contact form', 'Basic SEO setup', 'Free SSL & 1 year hosting', 'Social media links'],
                'excluded' => ['E-commerce features', 'Custom software modules']],
            ['name' => 'Professional Corporate Website', 'slug' => 'professional-corporate-website', 'category' => PackageCategory::Website,
                'price' => 35000, 'suffix' => 'one-time', 'delivery' => '2–3 weeks', 'support' => '6 months free support', 'recommended' => true, 'featured' => true,
                'features' => ['Up to 15 pages', 'Custom design', 'Admin panel for content', 'Blog system', 'Advanced SEO & analytics', 'Bengali + English support', 'Free SSL & 1 year hosting'],
                'excluded' => ['Online payments']],
            ['name' => 'E-commerce Website', 'slug' => 'ecommerce-website', 'category' => PackageCategory::Ecommerce,
                'price' => 60000, 'suffix' => 'starting', 'starting' => true, 'delivery' => '4–6 weeks', 'support' => '6 months free support', 'recommended' => false, 'featured' => true,
                'features' => ['Unlimited products', 'bKash / Nagad / card payments', 'Courier integration', 'Inventory & order management', 'Discount & coupon system', 'Sales reports'],
                'excluded' => []],
            ['name' => 'Custom Software', 'slug' => 'custom-software', 'category' => PackageCategory::Software,
                'price' => null, 'suffix' => 'project-based', 'delivery' => 'Scoped per project', 'support' => 'SLA-based support', 'recommended' => false, 'featured' => false,
                'features' => ['Requirement analysis', 'Custom modules & workflows', 'Role-based access', 'Reports & dashboards', 'Training & documentation', 'Source code ownership'],
                'excluded' => []],
            ['name' => 'Website Maintenance', 'slug' => 'website-maintenance', 'category' => PackageCategory::Maintenance,
                'price' => 3000, 'suffix' => '/month', 'delivery' => 'Ongoing', 'support' => 'Priority response', 'recommended' => false, 'featured' => false,
                'features' => ['Weekly backups', 'Security updates', 'Uptime monitoring', '2 hours of content changes/month', 'Monthly report'],
                'excluded' => ['New feature development']],
            ['name' => 'Social Media Management', 'slug' => 'social-media-management', 'category' => PackageCategory::SocialMedia,
                'price' => 8000, 'suffix' => '/month', 'delivery' => 'Ongoing', 'support' => 'Dedicated manager', 'recommended' => false, 'featured' => false,
                'features' => ['12 posts per month', 'Custom graphics', 'Page moderation', 'Monthly analytics report', 'Boosting management (ad budget separate)'],
                'excluded' => ['Ad budget']],
            ['name' => 'Digital Marketing', 'slug' => 'digital-marketing-package', 'category' => PackageCategory::Marketing,
                'price' => 15000, 'suffix' => '/month', 'starting' => true, 'delivery' => 'Ongoing', 'support' => 'Monthly strategy call', 'recommended' => false, 'featured' => false,
                'features' => ['SEO optimization', 'Google Ads management', 'Facebook Ads management', 'Conversion tracking', 'Monthly performance report'],
                'excluded' => ['Ad budget']],
            ['name' => 'Hosting & Business Email', 'slug' => 'hosting-business-email', 'category' => PackageCategory::Hosting,
                'price' => 4000, 'suffix' => '/year', 'starting' => true, 'delivery' => 'Same day', 'support' => '24/7 support', 'recommended' => false, 'featured' => false,
                'features' => ['10 GB NVMe storage', 'Free SSL', 'Business email accounts', 'Daily backups', 'cPanel access'],
                'excluded' => []],
        ];

        foreach ($packages as $index => $data) {
            Package::firstOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'category' => $data['category'],
                'excerpt' => 'Demo pricing — adjust from the admin panel before publishing.',
                'price' => $data['price'],
                'price_suffix' => $data['suffix'] === 'starting' ? 'one-time' : $data['suffix'],
                'is_starting_from' => $data['starting'] ?? false,
                'features' => $data['features'],
                'excluded_features' => $data['excluded'],
                'delivery_time' => $data['delivery'],
                'support_period' => $data['support'],
                'is_recommended' => $data['recommended'],
                'is_featured' => $data['featured'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    protected function seedHostingPlans(): void
    {
        $plans = [
            ['name' => 'Starter', 'type' => HostingPlanType::Shared, 'monthly' => 150, 'yearly' => 1500, 'storage' => '5 GB NVMe', 'websites' => '1 website', 'email' => '5 accounts', 'db' => '2 databases', 'recommended' => false],
            ['name' => 'Business', 'type' => HostingPlanType::Shared, 'monthly' => 300, 'yearly' => 3000, 'storage' => '15 GB NVMe', 'websites' => '3 websites', 'email' => '20 accounts', 'db' => '10 databases', 'recommended' => true],
            ['name' => 'Managed Pro', 'type' => HostingPlanType::Managed, 'monthly' => 1200, 'yearly' => 12000, 'storage' => '30 GB NVMe', 'websites' => '5 websites', 'email' => 'Unlimited', 'db' => 'Unlimited', 'recommended' => false],
            ['name' => 'VPS 2', 'type' => HostingPlanType::Vps, 'monthly' => 2500, 'yearly' => 27000, 'storage' => '60 GB NVMe · 2 vCPU · 4 GB RAM', 'websites' => 'Unlimited', 'email' => 'Self-managed', 'db' => 'Unlimited', 'recommended' => false],
            ['name' => 'VPS 4', 'type' => HostingPlanType::Vps, 'monthly' => 4500, 'yearly' => 48000, 'storage' => '120 GB NVMe · 4 vCPU · 8 GB RAM', 'websites' => 'Unlimited', 'email' => 'Self-managed', 'db' => 'Unlimited', 'recommended' => true],
            ['name' => 'Cloud Business', 'type' => HostingPlanType::Cloud, 'monthly' => 8000, 'yearly' => 90000, 'storage' => 'Scalable block storage', 'websites' => 'Unlimited', 'email' => 'Optional add-on', 'db' => 'Managed MySQL', 'recommended' => false],
            ['name' => 'Email Basic', 'type' => HostingPlanType::Email, 'monthly' => 100, 'yearly' => 1000, 'storage' => '10 GB / mailbox', 'websites' => '—', 'email' => 'Per-mailbox pricing', 'db' => '—', 'recommended' => false],
        ];

        foreach ($plans as $index => $data) {
            HostingPlan::firstOrCreate(
                ['name' => $data['name'], 'type' => $data['type']],
                [
                    'monthly_price' => $data['monthly'],
                    'yearly_price' => $data['yearly'],
                    'storage' => $data['storage'],
                    'bandwidth' => 'Unmetered',
                    'websites' => $data['websites'],
                    'email_accounts' => $data['email'],
                    'databases' => $data['db'],
                    'backup_frequency' => 'Daily',
                    'has_ssl' => true,
                    'support_level' => '24/7 support',
                    'features' => ['Free SSL certificate', 'Daily off-site backups', 'Malware scanning', '99.9% uptime target (demo)'],
                    'is_recommended' => $data['recommended'],
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }

    protected function seedTestimonials(): void
    {
        $testimonials = [
            ['author' => 'Demo Client — Retail', 'designation' => 'Managing Director', 'company' => 'Demo Fashion Ltd.', 'quote' => 'Jamuna Soft delivered our online store on time and the support after launch has been excellent. (Demo testimonial — replace with a real client quote.)'],
            ['author' => 'Demo Client — Education', 'designation' => 'Principal', 'company' => 'Demo Academy', 'quote' => 'Our admission process is now fully online and parents love it. (Demo testimonial.)'],
            ['author' => 'Demo Client — Corporate', 'designation' => 'Head of Operations', 'company' => 'Demo Group', 'quote' => 'The custom ERP replaced five spreadsheets and two apps. Reporting that took days now takes minutes. (Demo testimonial.)'],
            ['author' => 'Demo Client — Hospitality', 'designation' => 'Owner', 'company' => 'Demo Resort', 'quote' => 'Bookings doubled within three months of the new website and campaigns. (Demo testimonial.)'],
        ];

        foreach ($testimonials as $index => $data) {
            Testimonial::firstOrCreate(
                ['author_name' => $data['author']],
                [
                    'author_designation' => $data['designation'],
                    'company' => $data['company'],
                    'quote' => $data['quote'],
                    'rating' => 5,
                    'is_approved' => true,
                    'is_featured' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }

    protected function seedTeam(): void
    {
        $members = [
            ['name' => 'Demo Founder', 'designation' => 'Founder & CEO', 'bio' => 'Demo profile — add your real team from the admin panel.'],
            ['name' => 'Demo Engineer', 'designation' => 'Lead Software Engineer', 'bio' => 'Demo profile.'],
            ['name' => 'Demo Marketer', 'designation' => 'Digital Marketing Lead', 'bio' => 'Demo profile.'],
        ];

        foreach ($members as $index => $data) {
            TeamMember::firstOrCreate(['name' => $data['name']], [
                'designation' => $data['designation'],
                'bio' => $data['bio'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    protected function seedBlog(): void
    {
        $author = User::role('Super Admin')->first() ?? User::first();

        $webDev = BlogCategory::firstOrCreate(['slug' => 'web-development'], ['name' => 'Web Development', 'sort_order' => 0]);
        $business = BlogCategory::firstOrCreate(['slug' => 'business-growth'], ['name' => 'Business Growth', 'sort_order' => 1]);
        $hosting = BlogCategory::firstOrCreate(['slug' => 'hosting-security'], ['name' => 'Hosting & Security', 'sort_order' => 2]);

        $tags = collect(['laravel', 'seo', 'ecommerce', 'security', 'small-business'])
            ->map(fn (string $slug) => BlogTag::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]));

        $posts = [
            ['category' => $webDev, 'title' => 'Why Every Business in Bangladesh Needs a Website in '.date('Y'), 'slug' => 'why-every-business-needs-a-website',
                'tags' => ['seo', 'small-business'], 'featured' => true],
            ['category' => $business, 'title' => 'How to Choose Between a Ready-made and Custom Software Solution', 'slug' => 'ready-made-vs-custom-software',
                'tags' => ['small-business'], 'featured' => false],
            ['category' => $hosting, 'title' => '7 Signs Your Website Hosting Is Holding Your Business Back', 'slug' => 'signs-your-hosting-is-holding-you-back',
                'tags' => ['security', 'seo'], 'featured' => false],
        ];

        foreach ($posts as $index => $data) {
            $post = BlogPost::firstOrCreate(['slug' => $data['slug']], [
                'blog_category_id' => $data['category']->id,
                'user_id' => $author?->id,
                'title' => $data['title'],
                'excerpt' => 'Demo article seeded for preview — replace with real content from the admin panel.',
                'content' => '<p>This is a demo blog post created by the seeder so the blog layout can be previewed. Replace it with real content from the admin panel.</p><h2>Why this matters</h2><p>Search engines reward useful, original content. Publishing consistently builds trust with both Google and your future clients.</p><h2>What to do next</h2><p>Write about the problems your customers actually ask you about — pricing, timelines, technology choices and maintenance.</p>',
                'status' => PublishStatus::Published,
                'published_at' => now()->subDays(($index + 1) * 7),
                'is_featured' => $data['featured'],
            ]);

            $post->tags()->syncWithoutDetaching(
                $tags->filter(fn ($tag) => in_array($tag->slug, $data['tags'], true))->pluck('id'),
            );
        }
    }

    protected function seedFaqs(): void
    {
        $faqs = [
            ['q' => 'How much does a website cost?', 'a' => 'A standard business website starts from around ৳15,000 depending on pages and features. E-commerce and custom software are quoted after a short requirement discussion. Every quotation includes a clear scope of work.'],
            ['q' => 'How long does it take to build a website?', 'a' => 'A starter website typically takes 7–10 days. Corporate websites take 2–3 weeks and e-commerce projects 4–6 weeks, depending on content readiness and feedback speed.'],
            ['q' => 'Do you provide hosting and domain registration?', 'a' => 'Yes. We provide fast SSD hosting, business email, SSL certificates and can register or transfer your domain — everything managed in one place.'],
            ['q' => 'Will I be able to update the website myself?', 'a' => 'Yes. Every website ships with an easy admin panel where you can edit text, images, services, blog posts and more without any coding.'],
            ['q' => 'Do you offer support after the project is delivered?', 'a' => 'Yes. Every project includes a free support period, and affordable monthly maintenance plans keep your site updated, backed up and secure afterwards.'],
            ['q' => 'Can you build websites in Bengali?', 'a' => 'Absolutely. We build bilingual (Bengali + English) websites with a language switcher, so you can reach both local and international customers.'],
            ['q' => 'What payment methods do you accept?', 'a' => 'We accept bank transfer, bKash and Nagad. Projects are typically split into milestone payments agreed in the quotation.'],
            ['q' => 'Do you sign NDAs or agreements?', 'a' => 'Yes. We are happy to sign an NDA and every project comes with a written scope of work and service agreement.'],
        ];

        foreach ($faqs as $index => $data) {
            Faq::firstOrCreate(['question' => $data['q']], [
                'answer' => $data['a'],
                'is_active' => true,
                'is_featured' => $index < 6,
                'sort_order' => $index,
            ]);
        }
    }

    protected function seedPolicyPages(): void
    {
        $pages = [
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy'],
            ['title' => 'Terms and Conditions', 'slug' => 'terms-and-conditions'],
            ['title' => 'Refund Policy', 'slug' => 'refund-policy'],
            ['title' => 'Cookie Policy', 'slug' => 'cookie-policy'],
            ['title' => 'Service Agreement Summary', 'slug' => 'service-agreement'],
        ];

        foreach ($pages as $data) {
            Page::firstOrCreate(['slug' => $data['slug']], [
                'title' => $data['title'],
                'template' => PageTemplate::Policy,
                'content' => '<p><strong>This is placeholder text.</strong> Replace it with your real '.strtolower($data['title']).' from the admin panel before going live.</p><p>This page was seeded so the link structure and layout can be previewed.</p>',
                'status' => PublishStatus::Published,
            ]);
        }
    }

    protected function seedSocialLinks(): void
    {
        $links = [
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://www.facebook.com/'],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/'],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://www.youtube.com/'],
        ];

        foreach ($links as $index => $data) {
            SocialLink::firstOrCreate(['platform' => $data['platform']], [
                'label' => $data['label'],
                'url' => $data['url'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
