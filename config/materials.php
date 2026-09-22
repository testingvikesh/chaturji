<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Material uploads / textbook PDF base path
    |--------------------------------------------------------------------------
    |
    | Absolute folder where material PDFs are stored (contains the uploaded files).
    | Example: D:/xampp/htdocs/backup/mehulbhai/tools/public/uploads
    |
    | DB field materials.material_attachment may store:
    | - public/uploads/file.pdf
    | - uploads/file.pdf
    | - file.pdf
    |
    */

    'uploads_path' => env('MATERIAL_UPLOADS_PATH', public_path('uploads')),

    /*
    |--------------------------------------------------------------------------
    | Optional project root for material files
    |--------------------------------------------------------------------------
    |
    | If set, paths like public/uploads/file.pdf are resolved from this root.
    | Example: D:/xampp/htdocs/backup/mehulbhai/tools
    |
    */

    'base_path' => env('MATERIAL_BASE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Public base URL for textbook PDFs (Material Generator)
    |--------------------------------------------------------------------------
    |
    | Used by the Textbook modal when the file is not on this server.
    | Example: https://gseschaturji.xyz
    | Attachment public/uploads/file.pdf → https://gseschaturji.xyz/public/uploads/file.pdf
    |
    */

    'public_url' => rtrim((string) env('MATERIAL_PUBLIC_URL', 'https://gseschaturji.xyz'), '/'),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 (same bucket as Material Generator / tools)
    |--------------------------------------------------------------------------
    */

    's3' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Teacher chapter detail visibility
    |--------------------------------------------------------------------------
    |
    | Previously limited by TEACHER_CHAPTER_DETAIL_IPS. IP restriction removed —
    | Introduction, Trailer, Importance, Knowledge Ladder, etc. are shown to all.
    |
    */

    'teacher_chapter_detail_ips' => [],

];
