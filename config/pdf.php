<?php

return [

    'font_size' => (int) env('PDF_WATERMARK_FONT_SIZE', 40),

    'font_color' => [
        (int) env('PDF_WATERMARK_COLOR_R', 180),
        (int) env('PDF_WATERMARK_COLOR_G', 180),
        (int) env('PDF_WATERMARK_COLOR_B', 180),
    ],

    'rotation_angle' => (int) env('PDF_WATERMARK_ROTATION', 45),

    'ghostscript_binary' => env('GHOSTSCRIPT_BINARY', 'gs'),

    'normalizer_timeout' => (int) env('PDF_NORMALIZER_TIMEOUT', 120),

];
