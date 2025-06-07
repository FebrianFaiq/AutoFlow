<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Mobil;

class MobilController extends Controller
{
    // Tambahkan method index() untuk “GET /mobils”
    public function index()
    {
        // Mengembalikan semua record mobil sebagai JSON
        return response()->json(Mobil::all());
    }

    // Tambahkan method show() untuk “GET /mobils/{id}”
    public function show($mobil_id)
    {
        $mobil = Mobil::find($mobil_id);
        if (! $mobil) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json($mobil);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama'      => 'required|string',
            'merk'      => 'required|string',
            'amount'     => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'nullable|image|max:5120',
        ]);

        $data = $request->only(['nama', 'merk', 'amount', 'deskripsi']);

        if ($request->hasFile('gambar')) {
            $file     = $request->file('gambar');
            $filename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();

            // Pindahkan ke folder public/images (gunakan base_path atau app('path.public') jika sudah bind)
            $publicDir = base_path('public'); 
            $file->move($publicDir . '/images', $filename);

            // Simpan URL lengkap di kolom gambar:
            $data['gambar'] = env('APP_URL') . '/images/' . $filename;
        }

        $mobil = Mobil::create($data);
        return response()->json($mobil, 201);
    }

    public function update(Request $request, $mobil_id)
    {
        $mobil = Mobil::find($mobil_id);
        if (! $mobil) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $this->validate($request, [
            'nama'      => 'sometimes|required|string',
            'merk'      => 'sometimes|required|string',
            'amount'     => 'sometimes|required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'nullable|image|max:5120',
        ]);

        $data = $request->only(['nama', 'merk', 'amount', 'deskripsi']);

        if ($request->hasFile('gambar')) {
            // Hapus file lama jika ada
            if ($mobil->gambar) {
                $oldPath     = parse_url($mobil->gambar, PHP_URL_PATH); // misal: "/images/xxx.jpg"
                $absoluteOld = base_path('public') . $oldPath;
                if (file_exists($absoluteOld)) {
                    @unlink($absoluteOld);
                }
            }

            $file     = $request->file('gambar');
            $filename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $publicDir = base_path('public');
            $file->move($publicDir . '/images', $filename);

            $data['gambar'] = env('APP_URL') . '/images/' . $filename;
        }

        $mobil->update($data);
        return response()->json($mobil);
    }

    public function destroy($mobil_id)
    {
        $mobil = Mobil::find($mobil_id);
        if (! $mobil) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if ($mobil->gambar) {
            $oldPath     = parse_url($mobil->gambar, PHP_URL_PATH);
            $absoluteOld = base_path('public') . $oldPath;
            if (file_exists($absoluteOld)) {
                @unlink($absoluteOld);
            }
        }

        $mobil->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
