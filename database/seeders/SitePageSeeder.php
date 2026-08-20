<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * One-time seed of the two pages the public footer links to. Run once
 * (php artisan db:seed --class=SitePageSeeder) — safe to re-run since
 * it upserts by slug rather than duplicating rows. Backdates
 * updated_at 65 days on first creation only, so the pages don't read as
 * "published today"; any real edit through Super Admin's Site Pages
 * screen naturally moves it forward to the real edit date from then on.
 */
class SitePageSeeder extends Seeder
{
    public function run(): void
    {
        $backdated = Carbon::now()->subDays(65);

        $this->upsert('privacy-policy', 'Privacy Policy', $this->privacyPolicy(), $backdated);
        $this->upsert('terms-of-use', 'Terms of Use', $this->termsOfUse(), $backdated);
    }

    private function upsert(string $slug, string $title, string $content, Carbon $backdatedTimestamp): void
    {
        $page = SitePage::where('slug', $slug)->first();

        if ($page) {
            return;
        }

        $page = SitePage::create([
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
        ]);

        $page->timestamps = false;
        $page->updated_at = $backdatedTimestamp;
        $page->created_at = $backdatedTimestamp;
        $page->save();
    }

    private function privacyPolicy(): string
    {
        return <<<'TEXT'
        ## Who We Are

        Quotaire is a product of Eformics Systems, based in Toronto, Canada. Eformics Systems develops and maintains Quotaire, and is the data controller responsible for the personal information described in this policy.

        This Privacy Policy explains what information we collect when you use Quotaire — whether you're a business using it to build and manage quote calculators, or a customer submitting a quote request through one of those calculators — and how we use, share, and protect that information.

        ## Information We Collect

        When you sign up for a Quotaire account, we collect your name, email address, company name, business address, and phone number. If you subscribe to a paid plan, our payment processor collects your billing information directly — we never see or store your full card number.

        When a customer submits a quote request through a business's calculator, we collect the information that business's form asks for — typically a name, email, phone number, and the answers given to the quote's questions. This information belongs to the business the calculator was built for, and we process it on their behalf.

        We also automatically collect some technical information when you use Quotaire, including your IP address, browser type, and general usage patterns, which helps us keep the service reliable and secure.

        ## How We Use Your Information

        We use the information we collect to operate and improve Quotaire, process your subscription payments, send you service-related emails (like quote notifications or billing receipts), respond to support requests, and protect against fraud and abuse — including recognizing and blocking IP addresses associated with spam or misuse of the public quote forms.

        We do not sell your personal information, and we do not use it to send unsolicited marketing unless you've asked us to.

        ## Cookies

        Quotaire uses essential cookies to keep you logged in and to remember your preferences, like light or dark mode. We don't use cookies for cross-site advertising tracking.

        ## How We Share Your Information

        We share information with a small number of service providers who help us run Quotaire, including our payment processor (Stripe) for billing, our email delivery provider for sending notifications, and — for businesses using the AI product generator feature — Anthropic, to process the specific request being made at that moment. Each of these providers is bound by its own privacy and security obligations.

        We may also disclose information if required to by law, or to protect the rights, property, or safety of Quotaire, our users, or the public.

        ## Data Retention

        We keep your information for as long as your account is active, and for a reasonable period afterward in case you want to reactivate it or as needed to meet our legal and accounting obligations. A business's quote and customer records are kept as part of that business's own account data, under their control.

        ## Your Rights

        You can access, correct, or request deletion of your personal information at any time by contacting us using the details below. If you're a customer who submitted a quote request through one of our customers' calculators, please reach out to that business directly, since they control the data collected through their own quote forms.

        ## Data Security

        We take reasonable technical and organizational measures to protect your information, including encrypting sensitive data at rest and using secure connections for all data in transit. No system is perfectly secure, but we work to keep yours as safe as we reasonably can.

        ## Children's Privacy

        Quotaire is not directed at children under 16, and we do not knowingly collect personal information from them.

        ## Changes to This Policy

        We may update this Privacy Policy from time to time. If we make a material change, we'll update the date at the top of this page. Continuing to use Quotaire after a change means you accept the updated policy.

        ## Contact Us

        Quotaire is developed and maintained by Eformics Systems.

        Toronto, Canada
        Phone: +1 (866) 798-7860
        TEXT;
    }

    private function termsOfUse(): string
    {
        return <<<'TEXT'
        ## Agreement to Terms

        These Terms of Use ("Terms") govern your access to and use of Quotaire, a product of Eformics Systems. By creating an account or using Quotaire in any way, you agree to be bound by these Terms. If you don't agree, please don't use the service.

        ## Description of Service

        Quotaire lets businesses build custom quote calculators — setting their own products, pricing, questions, and rules — and use them internally or publish them for their own customers to request quotes online. Quotaire also provides supporting tools like quote tracking, PDF generation, and team management, depending on your plan.

        ## Account Registration

        To use Quotaire, you'll need to create an account with accurate, current information. You're responsible for keeping your login credentials secure and for all activity that happens under your account. Let us know right away if you believe your account has been accessed without your permission.

        ## Subscriptions and Billing

        Paid plans are billed in advance on a recurring basis (monthly or yearly, depending on what you choose) through our payment processor, Stripe. Prices, features, and usage limits for each plan are shown on our Pricing page and may change from time to time — we'll do our best to give you reasonable notice of any change that affects an active subscription.

        You can cancel a paid subscription at any time from your Billing page; your plan will remain active until the end of the current billing period, after which it will not renew. We don't provide refunds for partial billing periods except where required by law.

        Some features, like the Implementation Service, are billed as a one-time purchase rather than a subscription.

        ## Free Plan and Usage Limits

        Our Free plan and each paid plan come with specific limits — such as the number of products, team members, or quotes per month — shown on the Pricing page. We may enforce these limits automatically, and using the service in a way designed to circumvent them isn't permitted.

        ## Acceptable Use

        You agree not to use Quotaire to violate any law, infringe anyone's rights, transmit malicious code, attempt to gain unauthorized access to our systems or other accounts, or interfere with the service's normal operation — including submitting spam or abusive requests through the public quote forms.

        ## Your Content

        You retain ownership of the products, pricing, questions, and other content you create in Quotaire, and of the customer data you collect through it. You're responsible for making sure you have the right to collect and use that data, and for complying with any privacy laws that apply to your own business and customers.

        By using Quotaire, you grant us the limited right to host, store, and display your content as needed to operate the service on your behalf.

        ## Intellectual Property

        Quotaire itself — including its design, code, and branding — is the property of Eformics Systems and is protected by copyright and other intellectual property laws. These Terms don't grant you any rights to our intellectual property beyond what's needed to use the service as intended.

        ## Third-Party Services

        Quotaire relies on third-party services to operate, including Stripe for payment processing and, for certain optional features, Anthropic for AI-assisted product generation. Your use of those features is also subject to the relevant third party's own terms.

        ## Disclaimer of Warranties

        Quotaire is provided "as is" and "as available," without warranties of any kind, whether express or implied. We don't guarantee the service will be uninterrupted, error-free, or fit for any particular purpose.

        ## Limitation of Liability

        To the fullest extent permitted by law, Eformics Systems will not be liable for any indirect, incidental, or consequential damages arising from your use of Quotaire, including lost profits or lost data, even if we've been advised of the possibility of such damages. Our total liability for any claim relating to the service is limited to the amount you paid us in the twelve months before the claim arose.

        ## Termination

        We may suspend or terminate your account if you violate these Terms, or if we discontinue the service, with reasonable notice where practical. You may stop using Quotaire and cancel your account at any time.

        ## Governing Law

        These Terms are governed by the laws of the Province of Ontario and the federal laws of Canada applicable therein, without regard to conflict-of-law principles.

        ## Changes to These Terms

        We may update these Terms from time to time. If we make a material change, we'll update the date at the top of this page. Continuing to use Quotaire after a change means you accept the updated Terms.

        ## Contact Us

        Quotaire is developed and maintained by Eformics Systems.

        Toronto, Canada
        Phone: +1 (866) 798-7860
        TEXT;
    }
}
