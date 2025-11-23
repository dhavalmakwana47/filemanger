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
                    {{-- Navigation items commented as before --}}
                </nav>
                <!-- Login Button -->
                <div class="hidden md:flex items-center justify-end md:flex-1 lg:w-0">
                    <a href="{{ route('login') }}" class="ml-8 whitespace-nowrap inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200 hover:scale-105">Login</a>
                </div>
                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-200">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-200">Login</a>
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
        <!-- ... your existing features section ... -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-down">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Powerful Features for Your Files</h2>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">Discover why FileManager is the best choice for secure file handling.</p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Your 4 feature cards remain unchanged -->
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
                <!-- Repeat other 3 cards exactly as before -->
                <!-- Feature Card 2, 3, 4 ... -->
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

    <!-- Contact Details Section -->
    <section class="py-20 bg-gradient-to-b from-gray-50 to-gray-100" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Get in Touch</h2>
                <p class="mt-4 text-xl text-gray-600">We’re here to help you with any questions or support</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <!-- Email -->
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Email Us</h3>
                    <p class="mt-2 text-gray-600">info@datasafehub.in</p>
                    <a href="mailto:info@datasafehub.in" class="inline-block mt-3 text-indigo-600 font-medium hover:underline">Send Email →</a>
                </div>

                <!-- Phone -->
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Call Us</h3>
                    <p class="mt-2 text-gray-600">+91 79908 22351</p>
                    <a href="tel:+917990822351" class="inline-block mt-3 text-indigo-600 font-medium hover:underline">Call Now →</a>
                </div>

                <!-- Contact Person -->
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Contact Person</h3>
                    <p class="mt-2 text-gray-600 font-medium">Dixit Prajapati</p>
                    <p class="text-sm text-gray-500 mt-1">Support & Sales Head</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-center md:text-left mb-6 md:mb-0">
                    <p class="text-base text-gray-500">&copy; 2025 THE APEXRISE CONSULTANT AND E-SERVICES. All rights reserved.</p>
                    <p class="text-sm text-gray-400 mt-2">Powered by DataSafeHub</p>
                </div>
                <div class="flex space-x-8">
                    <a href="#" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Privacy Policy</a>
                    <a href="#" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Terms of Service</a>
                    <a href="mailto:info@datasafehub.in" class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">Contact</a>
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