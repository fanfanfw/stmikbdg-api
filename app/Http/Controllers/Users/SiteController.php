<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ErrorHandler;
use App\Exceptions\ExcelImportException;
use App\Http\Controllers\Controller;
use App\Imports\ImportUserSiteAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

// ? Models - table
use App\Models\Users\Site;
use App\Models\Users\User;
use App\Models\Users\UserSite;

// ? Models - view
use App\Models\Users\UserSitesView;
use App\Models\Users\UserView;

class SiteController extends Controller
{
    public function getAll(Request $request) {
        try {
            $siteId = $request->query('site_id');
            $siteRole = $request->query('site_role');

            if ($siteRole) {
                $sites = self::getSitesByRole($siteRole);

                return $this->successfulResponseJSON([
                    'sites' => $sites
                ]);
            }

            if ($siteId) {
                $site = Site::where('id', (int) $siteId)->first();
                $tempSiteUsers = UserSitesView::where('site_id', $siteId)
                    ->whereNot('user_id', auth()->user()->id)
                    ->orderBy('user_site_id', 'DESC')
                    ->get();

                return $this->successfulResponseJSON([
                    'site_detail' => $site,
                    'site_users' => $tempSiteUsers,
                ]);
            }

            $sites = Site::orderBy('id', 'DESC')->get();

            return $this->successfulResponseJSON([
                'sites' => $sites,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function addAccess(Request $request) {
        try {
            if ($request->query('import')) {
                if ($request->query('import') === 'excel') {
                    $request->validate([
                        'file' => 'required|mimes:xlsx,xls|max:2048'
                    ]);
                    $excel = $request->file('file');

                    return self::importUserSiteAccessFromExcel($excel);
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Metode import yang tersedia saat ini adalah menggunakan file excel'
                    ], 400);
                }
            }

            $request->validate([
                'user_id' => 'required|exists:users,id',
                'site_id' => 'required|exists:sites,id',
            ]);

            $checkAccess = UserSite::where('user_id', $request->user_id)
                ->where('site_id', $request->site_id)
                ->first();

            if ($checkAccess) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'User telah memiliki akses ke web tersebut'
                ], 400);
            }

            UserSite::insert([
                'user_id' => $request->user_id,
                'site_id' => $request->site_id,
            ]);

            return $this->successfulResponseJSON([
                'user_id' => $request->user_id,
                'site_id' => $request->site_id,
            ], 'Akses user ke web berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function addSite(Request $request) {
        try {
            $data = $request->validate([
                'url' => 'required|string',
                'name' => 'required|string',
                'is_dev' => 'required|boolean',
                'is_mhs' => 'required|boolean',
                'is_admin' => 'required|boolean',
                'is_dosen' => 'required|boolean',
                'is_doswal' => 'required|boolean',
                'is_prodi' => 'required|boolean',
                'is_wk' => 'required|boolean',
                'is_staff' => 'required|boolean',
                'is_secretary' => 'required|boolean',
                'is_pimpinan' => 'required|boolean'
            ]);

            $validatedURL = filter_var($request->url, FILTER_VALIDATE_URL);
            $data['url'] = $validatedURL;
            $urlExists = Site::where('url', 'like', '%'  . $validatedURL . '%')->first();

            if ($urlExists) {
                return $this->failedResponseJSON('Alamat web sudah pernah ditambahkan', 400);
            }

            DB::beginTransaction();
            $insert = Site::insert($data);

            if ($insert) {
                DB::commit();
                return $this->successfulResponseJSONV2('Alamat web berhasil ditambahkan', 201);
            }

            DB::rollBack();
            return $this->failedResponseJSON('Alamat web gagal ditambahkan', 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function deleteAccess(Request $request) {
        try {
            $request->validate([
                'site_id' => 'required|integer|exists:sites,id',
                'user_id'=> 'required|integer|exists:users,id',
            ]);

            DB::beginTransaction();
            $deletedAccess = UserSite::where('site_id', $request->site_id)
                ->where('user_id', $request->user_id)
                ->delete();

            /**
             * jika user memiliki role developer
             * maka ubah nilai is_dev menjadi false
             */
            $user = UserView::where('id', $request->user_id)->first();

            if ($user['is_dev']) {
                User::where('id', $request->user_id)
                    ->update([
                        'is_dev' => false
                    ]);
            }

            if ($deletedAccess) {
                DB::commit();
                return $this->successfulResponseJSON([
                    'user_id' => $request->user_id,
                    'site_id' => $request->site_id,
                ], 'Akses user ke url berhasil dihapus');
            }

            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Gagal menghapus akses user ke web'
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function addAllAccesses(Request $request) {
        try {
            $request->validate([
                'user_id' => 'integer|exists:users,id'
            ]);

            DB::beginTransaction();
            UserSite::where('user_id', $request->user_id)->delete();
            User::where('id', $request->user_id)
                ->update([
                    'is_dev' => true,
                    'is_dosen' => true,
                    'is_doswal' => true,
                    'is_prodi' => true,
                    'is_wk' => true,
                    'is_staff' => true,
                    'is_admin' => true
                ]);

            $siteIds = Site::select('id')->get();
            $userSites = [];

            foreach ($siteIds as $item) {
                array_push($userSites, [
                    'user_id' => $request->user_id,
                    'site_id' => $item['id']
                ]);
            }

            $insert = UserSite::insert($userSites);

            if ($insert) {
                DB::commit();
                return $this->successfulResponseJSONV2('Berhasil menambahkan semua akses untuk user');
            }

            DB::rollBack();
            return $this->failedResponseJSON('Gagal menambahkan semua akses');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getDetailSite($siteId) {
        try {
            $site = Site::where('id', (int) $siteId)->first();

            if ($site) {
                $tempSite = [
                    'id' => $site['id'],
                    'name' => $site['name'],
                    'url' => $site['url']
                ];

                $availableRoles = collect($site)->filter(function ($item) {
                    return $item === true;
                });

                $tempSite['roles'] = $availableRoles;

                return $this->successfulResponseJSON([
                    'site' => $tempSite
                ]);
            }

            return $this->failedResponseJSON('Site tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function addAccessAndRoles(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'site_id' => 'required|exists:sites,id',
                'roles' => 'required'
            ]);

            if (count($request->roles) < 1) {
                return $this->failedResponseJSON('Nilai roles diperlukan', 400);
            }

            /**
             * Jika user telah memiliki akses ke sistem tersebut
             * maka hapus yang lama dan ganti dengan yang baru
             */
            DB::beginTransaction();
            UserSite::where('user_id', $request->user_id)
                ->where('site_id', $request->site_id)
                ->delete();

            /**
             * Update role user terlebih dahulu
             * jika berhasil maka baru tambahkan akses ke site
             */
            $updateUserRoles = User::where('id', $request->user_id)->update($request->roles);

            if ($updateUserRoles) {
                $addAccess = UserSite::insert([
                    'user_id' => $request->user_id,
                    'site_id' => $request->site_id
                ]);

                if ($addAccess) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Akses user berhasil ditambahkan');
                }
            }

            DB::rollBack();
            return $this->failedResponseJSON('Akses user gagal ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getUserSites() {
        try {
            $userId = auth()->user()->id;
            $userSites = UserSitesView::where('user_id', $userId)->get(['url', 'name']);

            return $this->successfulResponseJSON([
                'user_sites' => $userSites
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function getSitesByRole($role) {
        switch ($role) {
            case 'dev':
                $sites = Site::where('is_dev', true)
                    ->select('id', 'url', 'name')
                    ->orderBy('id', 'DESC')
                    ->get();
                break;
            case 'mhs':
                $sites = Site::where('is_mhs', true)
                    ->select('id', 'url', 'name')
                    ->orderBy('id', 'DESC')
                    ->get();
                break;
            case 'dsn':
                $sites = Site::where('is_dosen', true)
                    ->select('id', 'url', 'name')
                    ->orderBy('id', 'DESC')
                    ->get();
                break;
            case 'stf':
                $sites = Site::where('is_staff', true)
                    ->select('id', 'url', 'name')
                    ->orderBy('id', 'DESC')
                    ->get();
                break;
            case 'adm':
                $sites = Site::where('is_admin', true)
                    ->select('id', 'url', 'name')
                    ->orderBy('id', 'DESC')
                    ->get();
                break;
            default:
                $sites = [];
                break;
        }

        return $sites;
    }

    //jangan dulu dipake
    public function importUserSiteAccessFromExcel($excel) {
        try {
            $fileName = $excel->hashName();
            $path = $excel->storeAs('public/excel/', $fileName);

            Excel::import(new ImportUserSiteAccess, storage_path('app/public/excel/' . $fileName));
            Storage::delete($path);

            return response()->json([
                'status' => 'success',
                'message' => 'Akses user ke web berhasil ditambahkan'
            ], 201);
        } catch (ExcelImportException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
