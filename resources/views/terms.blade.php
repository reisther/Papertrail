<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms and Conditions - Paper Trail</title>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-blue-600">Paper Trail</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Home</a>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Login</a>
                </div>
            </div>
        </div>
    </nav>
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Terms and Conditions</h1>
                    <p class="text-gray-600">Paper Trail - Thesis Management Platform</p>
                    <p class="text-sm text-gray-500 mt-2">Last updated: {{ date('F j, Y') }}</p>
                </div>

                <div class="prose prose-lg max-w-none">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <p class="text-blue-800 font-medium">
                            By using Paper Trail, you agree with our Terms and Privacy Policy.
                        </p>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Platform Purpose</h2>
                    <p class="text-gray-700 mb-6">
                        This platform is designed for thesis management, group collaboration, progress tracking, and instructor feedback. Paper Trail serves as an academic tool to facilitate the thesis development process between students, advisers, and academic institutions.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">2. User Responsibilities</h2>
                    <p class="text-gray-700 mb-4">
                        You are responsible for the originality and accuracy of any content you share on the platform. You must ensure that:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li>All uploaded materials are original or properly cited</li>
                        <li>Content does not violate copyright laws</li>
                        <li>Academic integrity standards are maintained</li>
                        <li>The rights of others are respected</li>
                        <li>You do not upload inappropriate, offensive, or harmful content</li>
                    </ul>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Content Ownership and Usage</h2>
                    <p class="text-gray-700 mb-6">
                        Your content remains your property. However, by using Paper Trail, you grant us permission to store and share your content with your group members and instructors for academic purposes. This includes thesis documents, progress reports, comments, and any other materials uploaded to the platform.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Data Collection and Privacy</h2>
                    <p class="text-gray-700 mb-4">
                        We collect only the information needed to provide our services, including:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-2">
                        <li>Account details (name, email, institutional affiliation)</li>
                        <li>Thesis files and documents</li>
                        <li>Progress updates and communications</li>
                        <li>Usage data to improve platform functionality</li>
                    </ul>
                    <p class="text-gray-700 mb-6">
                        This data is used only within the platform and is not shared with third parties unless required by law or with your explicit consent.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Security</h2>
                    <p class="text-gray-700 mb-6">
                        We use reasonable security measures to protect your data, including encryption and secure access controls. However, we cannot guarantee complete protection against all security threats. You are responsible for keeping your login credentials secure and notifying us immediately of any unauthorized access to your account.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Limitation of Liability</h2>
                    <p class="text-gray-700 mb-6">
                        Paper Trail is not liable for service interruptions, data loss, or reliance on feedback provided through the platform. While we strive to maintain reliable service, users should maintain backup copies of important documents and understand that the platform is provided "as is" without warranties.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Updates to Terms</h2>
                    <p class="text-gray-700 mb-6">
                        We may update these terms from time to time to reflect changes in our services or legal requirements. Continued use of the platform means you accept any updates to these terms. We will notify users of significant changes through the platform or via email.
                    </p>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Contact Information</h2>
                    <p class="text-gray-700 mb-6">
                        For questions about these Terms and Conditions or our Privacy Policy, please contact us at:
                        <a href="mailto:support@papertrailpsu.com" class="text-blue-600 hover:text-blue-800 underline">support@papertrailpsu.com</a>
                    </p>

                    <div class="bg-gray-50 border rounded-lg p-4 mt-8">
                        <p class="text-sm text-gray-600 text-center">
                            By continuing to use Paper Trail, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.
                        </p>
                    </div>
                </div>

                <div class="text-center mt-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
