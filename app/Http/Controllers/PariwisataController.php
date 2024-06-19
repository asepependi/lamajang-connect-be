<?php

namespace App\Http\Controllers;

use View, DB;
use App\Models\Pariwisata;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Traits\MaskMoney;
use Illuminate\Support\Facades\File;

class PariwisataController extends Controller
{
    use MaskMoney;
    protected $model;
    protected $view     = 'pariwisata.';
    protected $route    = 'pariwisata.';

    public function __construct(Pariwisata $model)
    {
        $this->model = $model;
        View::share('view', $this->view);
        View::share('route', $this->route);
    }

    public function user()
    {
        return auth()->user();
    }

    public function index(Request $req)
    {
        if ($req->ajax()) {
            $data = $this->model->select();
            return DataTables::of($data)
            ->addColumn('action', function ($data) {
                return view('datatable.action', [
                    'model' => $data,
                    'edit_url' => route($this->route.'edit', $data->id)
                ]);
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
        }

        return view($this->view.'index');
    }

    public function create()
    {
        return view($this->view.'create');
    }

    public function store(Request $req)
    {
        DB::beginTransaction();
        try {
            $validator = \Validator::make(
                $req->all(),
                [
                    'name' => 'required',
                    'jam_buka' => 'required',
                    'alamat' => 'required',
                    'harga' => 'required',
                    'description' => 'required',
                ],
                [
                    'name.required' => 'Silahkan Masukkan Nama !',
                    'jam_buka.required' => 'Silahkan Masukkan Jam Buka !',
                    'alamat.required' => 'Silahkan Masukkan Alamat !',
                    'harga.required' => 'Silahkan Masukkan Harga !',
                    'description.required' => 'Silahkan Masukkan Deskripsi !'
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with($messages->first())->withInput();
            }

            DB::commit();
            $pariwisata = new $this->model();
            $pariwisata->nama = $req->name;
            $pariwisata->jam_buka = $req->jam_buka;
            $pariwisata->alamat = $req->alamat;
            $pariwisata->harga = $this->convert_rupiah($req->harga);
            if ($req->file('foto')) {
                $uploadFile = $req->file('foto');
                $fileName = $uploadFile->hashName();
                $uploadFile->store('pariwisata', 'public');
                $pariwisata->foto = $fileName;
            }
            $pariwisata->deskripsi = $req->description;
            $pariwisata->save();

            return redirect()->route($this->route.'index')->with('success', 'Data berhasil disimpan !');
        } catch (\Exception $err) {
            DB::rollback();
            $messages = $err->getMessage();
            return redirect()->back()->with('error', $messages);
        }
    }

    public function edit($id)
    {
        $data = $this->model::find($id);
        $data->harga = $this->rupiah($data->harga);

        return view($this->view.'edit', compact('data'));
    }

    public function update(Request $req, $id)
    {
        DB::beginTransaction();
        try {
            $validator = \Validator::make(
                $req->all(),
                [
                    'name' => 'required',
                    'jam_buka' => 'required',
                    'alamat' => 'required',
                    'harga' => 'required',
                    'description' => 'required',
                ],
                [
                    'name.required' => 'Silahkan Masukkan Nama !',
                    'jam_buka.required' => 'Silahkan Masukkan Jam Buka !',
                    'alamat.required' => 'Silahkan Masukkan Alamat !',
                    'harga.required' => 'Silahkan Masukkan Harga !',
                    'description.required' => 'Silahkan Masukkan Deskripsi !'
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with($messages->first())->withInput();
            }

            DB::commit();
            $pariwisata = $this->model::find($id);
            $pariwisata->nama = $req->name;
            $pariwisata->jam_buka = $req->jam_buka;
            $pariwisata->alamat = $req->alamat;
            $pariwisata->harga = $this->convert_rupiah($req->harga);
            if ($req->file('foto')) {
                $image_path = storage_path("app/public/pariwisata/{$pariwisata->foto}");
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }
                $uploadFile = $req->file('foto');
                $fileName = $uploadFile->hashName();
                $uploadFile->store('pariwisata', 'public');
                $pariwisata->foto = $fileName;
            }
            $pariwisata->deskripsi = $req->description;
            $pariwisata->update();

            return redirect()->route($this->route.'index')->with('success', 'Data berhasil diupdate !');
        } catch (\Exception $err) {
            DB::rollback();
            $messages = $err->getMessage();
            return redirect()->back()->with('error', $messages);
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        $status = false;
        try{
            DB::commit();
            $dataDel = $this->model::find($id);
            $image_path = storage_path("app/public/pariwisata/{$dataDel->foto}");
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
            $dataDel->delete();

            $status = true;
            $data['message'] = 'Data berhasil dihapus !';
            $response = [
                'status' => $status,
                'data' => $data
            ];

            return response()->json($response);
        }catch(\Exception $err){
            DB::rollback();
            $data['message'] = $err->getMessage();
            $response = [
                'status' => $status,
                'data' => $data
            ];
            return response()->json($response);
        }
    }
}