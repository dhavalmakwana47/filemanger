<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileManager - Secure File Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }
        @keyframes gradient-bg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient-bg 15s ease infinite;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full z-10 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4 md:justify-start md:space-x-10">
                <!-- Logo -->
                <div class="flex justify-start lg:w-0 lg:flex-1">
                    <a href="#=" class="text-2xl font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-200">FileManager</a>
                </div>
                <!-- Navigation -->
                <nav class="hidden md:flex space-x-10">
                    <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors duration-200">Home</a>
                    <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors duration-200">Features</a>
                    <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors duration-200">Pricing</a>
                    <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors duration-200">Contact</a>
                </nav>
                <!-- Login Button -->
                <div class="hidden md:flex items-center justify-end md:flex-1 lg:w-0">
                    <a href="{{ route('login') }}" class="ml-8 whitespace-nowrap inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200 hover:scale-105">Login</a>
                </div>
                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-200" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <!-- Heroicon name: outline/menu -->
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">Home</a>
                    <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">Features</a>
                    <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">Pricing</a>
                    <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">Contact</a>
                    <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-200">Login</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-indigo-600 to-blue-500 text-white pt-20 pb-32 animate-gradient" data-aos="fade-in" data-aos-duration="1000">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl" data-aos="zoom-in" data-aos-delay="200">Manage Your Files Securely and Effortlessly</h1>
            <p class="mt-6 max-w-2xl mx-auto text-xl text-indigo-100" data-aos="fade-up" data-aos-delay="400">The ultimate file manager for individuals and teams. Store, share, and organize with top-notch security.</p>
            <div class="mt-10" data-aos="fade-up" data-aos-delay="600">
                <a href="#" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10 transition-all duration-300 hover:scale-105 hover:shadow-lg">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-down">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Powerful Features for Your Files</h2>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">Discover why FileManager is the best choice for secure file handling.</p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Feature Card 1 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="100">
                    <div class="p-6">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto transition-transform duration-300 hover:rotate-12">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Privacy First</h3>
                        <p class="mt-2 text-base text-gray-500 text-center">Your data is encrypted and private, always under your control.</p>
                    </div>
                </div>
                <!-- Feature Card 2 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="200">
                    <div class="p-6">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto transition-transform duration-300 hover:rotate-12">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Advanced Security</h3>
                        <p class="mt-2 text-base text-gray-500 text-center">Multi-factor authentication and real-time threat detection.</p>
                    </div>
                </div>
                <!-- Feature Card 3 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="300">
                    <div class="p-6">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto transition-transform duration-300 hover:rotate-12">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.684 11.918 9.818 10.784 11.242 10.784c1.423 0 2.557 1.134 2.557 2.558 0 1.423-1.134 2.557-2.557 2.557-1.423 0-2.557-1.134-2.557-2.557zm5.858 0c0-1.843-1.5-3.342-3.342-3.342S8 11.499 8 13.342c0 1.842 1.5 3.342 3.342 3.342s3.342-1.5 3.342-3.342z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Easy File Sharing</h3>
                        <p class="mt-2 text-base text-gray-500 text-center">Share files securely with links that expire automatically.</p>
                    </div>
                </div>
                <!-- Feature Card 4 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="400">
                    <div class="p-6">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto transition-transform duration-300 hover:rotate-12">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Storage Management</h3>
                        <p class="mt-2 text-base text-gray-500 text-center">Organize, search, and manage storage with intuitive tools.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-20 bg-gray-50" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-down">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Simple Pricing for Everyone</h2>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">Choose a plan that fits your needs.</p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <!-- Free Plan -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 relative" data-aos="flip-left" data-aos-delay="100">
                    <div class="p-8">
                        <h3 class="text-lg font-medium text-gray-900 text-center">Free</h3>
                        <div class="mt-4 flex items-baseline justify-center">
                            <span class="text-5xl font-extrabold text-gray-900">$0</span>
                            <span class="ml-1 text-xl font-medium text-gray-500">/mo</span>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="text-base text-gray-500">5GB Storage</li>
                            <li class="text-base text-gray-500">Basic Security</li>
                            <li class="text-base text-gray-500">Limited Sharing</li>
                        </ul>
                        <div class="mt-8">
                            <a href="#" class="block w-full py-3 px-6 border border-transparent rounded-md text-base font-medium text-indigo-600 bg-indigo-100 hover:bg-indigo-200 transition-all duration-200 hover:scale-105 text-center">Sign Up</a>
                        </div>
                    </div>
                </div>
                <!-- Pro Plan -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 relative" data-aos="flip-left" data-aos-delay="200">
                    <div class="absolute inset-x-0 top-0 h-1 bg-indigo-600"></div>
                    <div class="p-8">
                        <h3 class="text-lg font-medium text-gray-900 text-center">Pro</h3>
                        <div class="mt-4 flex items-baseline justify-center">
                            <span class="text-5xl font-extrabold text-gray-900">$9</span>
                            <span class="ml-1 text-xl font-medium text-gray-500">/mo</span>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="text-base text-gray-500">50GB Storage</li>
                            <li class="text-base text-gray-500">Advanced Security</li>
                            <li class="text-base text-gray-500">Unlimited Sharing</li>
                        </ul>
                        <div class="mt-8">
                            <a href="#" class="block w-full py-3 px-6 border border-transparent rounded-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200 hover:scale-105 text-center">Get Started</a>
                        </div>
                    </div>
                </div>
                <!-- Enterprise Plan -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 relative" data-aos="flip-left" data-aos-delay="300">
                    <div class="p-8">
                        <h3 class="text-lg font-medium text-gray-900 text-center">Enterprise</h3>
                        <div class="mt-4 flex items-baseline justify-center">
                            <span class="text-5xl font-extrabold text-gray-900">$49</span>
                            <span class="ml-1 text-xl font-medium text-gray-500">/mo</span>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="text-base text-gray-500">Unlimited Storage</li>
                            <li class="text-base text-gray-500">Enterprise Security</li>
                            <li class="text-base text-gray-500">Team Collaboration</li>
                        </ul>
                        <div class="mt-8">
                            <a href="#" class="block w-full py-3 px-6 border border-transparent rounded-md text-base font-medium text-indigo-600 bg-indigo-100 hover:bg-indigo-200 transition-all duration-200 hover:scale-105 text-center">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-center md:text-left">
                    <p class="text-base text-gray-500">&copy; 2025 FileManager. All rights reserved.</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-6">
                    <a href="#" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Privacy Policy</a>
                    <a href="#" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Terms of Service</a>
                    <a href="#" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>

</body>
</html>