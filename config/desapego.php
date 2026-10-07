<?php

return [
    'site_name' => env('DESAPEGO_SITE_NAME', 'Desapego do Matheus'),
    'pix_label' => env('DESAPEGO_PIX_LABEL', 'Comprar no Pix'),

    // Você pode usar uma URL absoluta fornecida pelo Asaas ou colocar a imagem
    // em public/images/pix-asaas.png.
    'pix_qr_code_url' => env('DESAPEGO_PIX_QR_CODE_URL', ''),
    'pix_qr_code_path' => env('DESAPEGO_PIX_QR_CODE_PATH', 'images/pix-asaas.png'),
    'pix_copy_paste' => env('DESAPEGO_PIX_COPY_PASTE', ''),
];
