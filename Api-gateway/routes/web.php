<?php

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/** @var \Laravel\Lumen\Routing\Router $router */
$router->group(['prefix' => 'api'], function () use ($router) {


    // ==================================================
    // ENDPOINT AUTH
    // ==================================================
    $router->post('/auth/register', function (Request $request) {
        $client = new Client();
        try {
            $response = $client->post('http://localhost:8001/register', [
                'json' => $request->all()
            ]);
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Authentication service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->post('/auth/login', function (Request $request) {
        $client = new Client();
        try {
            $response = $client->post('http://localhost:8001/login', [
                'json' => $request->all()
            ]);
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Authentication service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->get('/auth/profile', function (Request $request) {
        $client = new Client();
        try {
            $response = $client->get('http://localhost:8001/profile', [
                'headers' => [
                    'Authorization' => $request->header('Authorization')
                ]
            ]);
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Authentication service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    // ==================================================
    // ENDPOINT CARS
    // ==================================================
    $router->get('/mobils', function (Request $request) {
        $client = new Client();
        try {
            $response = $client->get('http://localhost:8002/mobils', [
                'query' => $request->all()
            ]);
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Mobil service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->post('/mobils', function (Request $request) {
        $client = new Client();
        try {
            $nama = $request->input('nama');
            $merk = $request->input('merk');
            $amount = $request->input('amount');
            if (!$nama || !$merk || !$amount) {
                return response()->json(['error' => 'Nama, merk, dan harga wajib diisi'], 422);
            }

            $multipart = [
                ['name' => 'nama', 'contents' => $nama],
                ['name' => 'merk', 'contents' => $merk],
                ['name' => 'amount', 'contents' => $amount],
                ['name' => 'deskripsi', 'contents' => $request->input('deskripsi', '')]
            ];

            if ($request->hasFile('gambar')) {
                $file = $request->file('gambar');
                if ($file->isValid()) {
                    $multipart[] = [
                        'name' => 'gambar',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                        'headers' => [
                            'Content-Type' => $file->getClientMimeType()
                        ]
                    ];
                }
            }

            $response = $client->post('http://localhost:8002/mobils', [
                'multipart' => $multipart
            ]);

            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : ['error' => 'Mobil service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->get('/mobils/{mobil_id}', function (Request $request, $mobil_id) {
        $client = new Client();
        try {
            $response = $client->get("http://localhost:8002/mobils/{$mobil_id}");
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Mobil service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->put('/mobils/{mobil_id}', function (Request $request, $mobil_id) {
        $client = new Client();
        try {
            // Cek apakah ada file upload
            $hasFile = $request->hasFile('gambar');
            
            if ($hasFile) {
                // Jika ada file, gunakan multipart dengan method override
                $multipart = [
                    ['name' => '_method', 'contents' => 'PUT'] // Method override
                ];
                
                $fields = ['nama', 'merk', 'amount', 'deskripsi'];
                foreach ($fields as $field) {
                    $value = $request->input($field);
                    if ($value !== null && $value !== '') {
                        $multipart[] = [
                            'name' => $field,
                            'contents' => (string)$value
                        ];
                    }
                }

                $file = $request->file('gambar');
                if ($file->isValid()) {
                    $multipart[] = [
                        'name' => 'gambar',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                        'headers' => [
                            'Content-Type' => $file->getClientMimeType()
                        ]
                    ];
                }

                // Gunakan POST dengan method override
                $response = $client->post("http://localhost:8002/mobils/{$mobil_id}", [
                    'multipart' => $multipart
                ]);
                
            } else {
                // Jika tidak ada file, gunakan JSON
                $data = array_filter($request->only(['nama', 'merk', 'amount', 'deskripsi']), function($value) {
                    return $value !== null && $value !== '';
                });
                
                if (empty($data)) {
                    return response()->json(['error' => 'No valid data provided for update'], 422);
                }

                $response = $client->put("http://localhost:8002/mobils/{$mobil_id}", [
                    'json' => $data,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]);
            }

            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
                
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : ['error' => 'Mobil service error'];
            
            // Log error untuk debugging
            if (env('APP_DEBUG', false)) {
                $debugInfo = [
                    'mobil_id' => $mobil_id,
                    'request_data' => $request->all(),
                    'error' => $errorMessage,
                    'status_code' => $statusCode
                ];
                file_put_contents(storage_path('logs/api_gateway_error.log'), json_encode($debugInfo, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
            }
            
            return response()->json($errorMessage, $statusCode);
        }
    });

    $router->delete('/mobils/{mobil_id}', function (Request $request, $mobil_id) {
        $client = new Client();
        try {
            $response = $client->delete("http://localhost:8002/mobils/{$mobil_id}");
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody() : ['error' => 'Mobil service error'];
            return response()->json($errorMessage, $statusCode);
        }
    });

    // ==================================================
    // ENDPOINT TRANSAKSI 
    // ==================================================
    
    // Endpoint untuk membeli (membutuhkan autentikasi)
    $router->post('/transaksi/beli', function (Request $request) {
        $client = new Client();
        try {
            // Pastikan header Authorization diteruskan ke service transaksi
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];
            
            // Teruskan Authorization header jika ada
            if ($request->header('Authorization')) {
                $headers['Authorization'] = $request->header('Authorization');
            }
            
            $response = $client->post('http://localhost:8003/api/transaksi/beli', [
                'json' => $request->all(),
                'headers' => $headers
            ]);
            
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
                
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? 
                json_decode($e->getResponse()->getBody(), true) : 
                ['error' => 'Transaksi service error'];
                
            return response()->json($errorMessage, $statusCode);
        }
    });

    // Endpoint untuk riwayat transaksi (membutuhkan autentikasi)
    $router->get('/transaksi/riwayat', function (Request $request) {
        $client = new Client();
        try {
            // Pastikan header Authorization diteruskan ke service transaksi
            $headers = [
                'Accept' => 'application/json'
            ];
            
            // Teruskan Authorization header jika ada
            if ($request->header('Authorization')) {
                $headers['Authorization'] = $request->header('Authorization');
            }
            
            $response = $client->get('http://localhost:8003/api/transaksi/riwayat', [
                'headers' => $headers,
                'query' => $request->all()
            ]);
            
            return response($response->getBody(), $response->getStatusCode())
                ->header('Content-Type', 'application/json');
                
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? 
                json_decode($e->getResponse()->getBody(), true) : 
                ['error' => 'Transaksi service error'];
                
            return response()->json($errorMessage, $statusCode);
        }
    });
});

// ==================================================
// ENDPOINT KHUSUS UNTUK MIDTRANS NOTIFICATION
// ==================================================
// Endpoint ini dipanggil langsung oleh Midtrans, tidak melalui autentikasi
$router->post('/api/transaksi/notification', function (Request $request) {
    $client = new Client();
    try {
        $response = $client->post('http://localhost:8003/api/transaksi/notification', [
            'json' => $request->all(),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
        
        return response($response->getBody(), $response->getStatusCode())
            ->header('Content-Type', 'application/json');
            
    } catch (RequestException $e) {
        $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
        $errorMessage = $e->hasResponse() ? 
            json_decode($e->getResponse()->getBody(), true) : 
            ['error' => 'Transaksi service error'];
            
        return response()->json($errorMessage, $statusCode);
    }
});