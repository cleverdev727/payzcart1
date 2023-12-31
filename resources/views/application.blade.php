<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ApiHuk - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
    @vite(['resources/js/main.js'])
</head>

<body>
    <div id="app">
        <div id="loading-bg">
            <div class="loading-logo">
                <!-- SVG Logo -->
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 253 253">
                    <defs>
                        <style>
                            .cls-1 {
                                fill: #0080ff;
                            }

                            .cls-2 {
                                fill: #097ef1;
                            }

                            .cls-3 {
                                fill: #3299ff;
                            }

                            .cls-4 {
                                fill: #fff;
                            }

                            .cls-5 {
                                fill: none;
                                stroke: #93b4d4;
                                stroke-width: 3.01px;
                            }

                            .cls-5,
                            .cls-6,
                            .cls-7 {
                                fill-rule: evenodd;
                            }

                            .cls-6 {
                                fill: #ff3f3f;
                            }

                            .cls-7 {
                                fill: #263a5b;
                            }

                            .cls-8 {
                                fill: #18d26b;
                            }
                        </style>
                    </defs>
                    <circle class="cls-1" cx="130.828" cy="123.719" r="90.828" />
                    <circle id="Ellipse_1_copy_4" data-name="Ellipse 1 copy 4" class="cls-2" cx="130.906"
                        cy="123.562" r="64.688" />
                    <circle id="Ellipse_1_copy_3" data-name="Ellipse 1 copy 3" class="cls-3" cx="130.828"
                        cy="123.75" r="60.922" />
                    <circle id="Ellipse_1_copy_2" data-name="Ellipse 1 copy 2" class="cls-4" cx="130.797"
                        cy="123.734" r="36.859" />
                    <path id="Ellipse_1_copy" data-name="Ellipse 1 copy" class="cls-5"
                        d="M100.377,8.589C164.363-8.5,230.133,29.344,247.278,93.108S226.451,222.413,162.464,239.5s-129.756-20.755-146.9-84.519S36.39,25.674,100.377,8.589Z" />
                    <path class="cls-6"
                        d="M10.511,87.313A14.14,14.14,0,1,1,.486,104.628,14.182,14.182,0,0,1,10.511,87.313Z" />
                    <path id="Ellipse_2_copy_2" data-name="Ellipse 2 copy 2" class="cls-7"
                        d="M225.771,44.665A10.566,10.566,0,1,1,218.29,57.6,10.582,10.582,0,0,1,225.771,44.665Z" />
                    <circle id="Ellipse_2_copy" data-name="Ellipse 2 copy" class="cls-8" cx="167.156" cy="235.875"
                        r="14.188" />
                </svg>



            </div>
            <div class=" loading">
                <div class="effect-1 effects"></div>
                <div class="effect-2 effects"></div>
                <div class="effect-3 effects"></div>
            </div>
        </div>
    </div>

    <script>
        const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
        const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'

        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

        if (primaryColor)
            document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
</body>

</html>
