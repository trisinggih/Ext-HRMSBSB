<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use Symfony\Component\HttpFoundation\Response;

class ExportExcelMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // For exporting, we force per_page to a high number if export is requested
        if ($request->has('export')) {
            $request->merge(['per_page' => 99999]);
        }

        $response = $next($request);

        $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;

        if ($request->has('export') && $statusCode == 200) {
            // Bypass if the controller already returned a streamed response or a file
            if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse || 
                $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                return $response;
            }

            $content = json_decode($response->getContent(), true);
            
            // Try to extract the items array
            $items = [];
            if (isset($content['data']['data'])) {
                $items = $content['data']['data'];
            } elseif (isset($content['data']) && is_array($content['data']) && !isset($content['data']['current_page'])) {
                $items = $content['data'];
            }
            
            if (count($items) > 0) {
                // Determine headers from first item if not explicitly requested
                $headers = array_keys($items[0]);
                
                // If columns are explicitly sent via request
                if ($request->has('export_columns')) {
                    $exportColumns = json_decode($request->export_columns, true);
                    $headers = array_values($exportColumns);
                    
                    // Map items
                    $newItems = [];
                    foreach ($items as $item) {
                        $newItem = [];
                        foreach ($exportColumns as $key => $label) {
                            $val = array_key_exists($key, $item) ? $item[$key] : $this->getNestedValue($item, $key);

                            // Human-readable formatting for common values
                            if (is_bool($val)) {
                                if ($key === 'is_active') {
                                    $val = $val ? 'Aktif' : 'Nonaktif';
                                } else {
                                    $val = $val ? 'Ya' : 'Tidak';
                                }
                            } elseif ($val === 'male') {
                                $val = 'Laki-laki';
                            } elseif ($val === 'female') {
                                $val = 'Perempuan';
                            } elseif ($val === 'single') {
                                $val = 'Lajang';
                            } elseif ($val === 'married') {
                                $val = 'Menikah';
                            } elseif ($val === 'divorced') {
                                $val = 'Bercerai';
                            } elseif ($val === 'permanent') {
                                $val = 'Tetap';
                            } elseif ($val === 'contract') {
                                $val = 'Kontrak';
                            } elseif ($val === 'probation') {
                                $val = 'Probation';
                            } elseif ($val === 'internship' || $val === 'intern') {
                                $val = 'Magang';
                            } elseif ($val === 'monthly') {
                                $val = 'Bulanan';
                            } elseif ($val === 'daily') {
                                $val = 'Harian';
                            } elseif (is_array($val) || is_object($val)) {
                                $val = json_encode($val);
                            }

                            $newItem[] = $val !== null && $val !== '' ? $val : '-';
                        }
                        $newItems[] = $newItem;
                    }
                    $items = $newItems;
                } else {
                    // Flatten arrays in items
                    foreach ($items as &$item) {
                        $newItem = [];
                        foreach ($headers as $h) {
                            $v = $item[$h] ?? null;
                            if (is_array($v) || is_object($v)) {
                                $v = json_encode($v);
                            }
                            $newItem[] = $v;
                        }
                        $item = $newItem;
                    }
                }
                
                $export = new GenericExport($items, $headers);
                $filename = 'export_' . date('Ymd_His') . '.xlsx';
                
                return Excel::download($export, $filename);
            }
        }
        
        return $response;
    }
    
    private function getNestedValue($array, $key) {
        $keys = explode('.', $key);
        $value = $array;
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        return $value;
    }
}
