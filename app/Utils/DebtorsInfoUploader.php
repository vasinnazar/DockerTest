<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use App\Utils\StrLib;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use \App\Utils\HelperUtil;
use App\MySoap;
use Log;
use App\Loan;
use App\LoanType;
use App\Synchronizer;
use Illuminate\Support\Facades\Storage;
use App\Customer;
use App\Passport;
use App\Debtor;
use App\UnfindCustomersPassports;
use App\about_client;
use App\Claim;
use App\User;
use App\Subdivision;
use App\Card;
use App\Promocode;
use App\Repayment;
use App\RepaymentType;
use App\PeacePay;
use App\MyResult;
use App\DebtorsOtherPhones;

class DebtorsInfoUploader {

    public function __construct() {
        set_time_limit(0);
    }

    public function getStoragePath($ftp = false) {
        if ($ftp) {
            return 'ftp://'
                    . config('filesystems.disks')['ftp']['username']
                    . ':'
                    . config('filesystems.disks')['ftp']['password']
                    . '@'
                    . config('filesystems.disks')['ftp']['host']
                    . config('filesystems.disks')['ftp']['root']
                    . '/debtors/';
        } else {
            return storage_path() . '/app/debtors/';
        }
    }

    public function copyFileFromFtp($filename) {
        return Storage::disk('local')->put('debtors/' . $filename, Storage::disk('ftp')->get('debtors/' . $filename));
    }

    /**
     * Загружает данные по должникам для переданных файлов
     * проходит в заданном порядке по переданным путям к файлам
     * если находит в путях нужный файл - загружает его
     * @param array $filenames
     */
    public function uploadByFilenames($filenames) {
        set_time_limit(0);
        $namesOrder = ['customers', 'passport', 'zayavka', 'uradress', 'factadress', 'cred', 'dopnik', 'mirovoe', 'zayavlenie_penya', 'rassrochka', 'sverka'];
//        $namesOrder = ['cred'];
        foreach ($namesOrder as $name) {
            $fname = ($name == 'customers') ? 'zayavka' : $name;
            $filename = '';
            foreach ($filenames as $f) {
                if (strpos($f, $fname) !== FALSE) {
                    $filename = str_replace("\r\n", '', $f);
                }
            }
            if ($filename == '') {
                continue;
            }
            try {
                $this->copyFileFromFtp($filename);
                switch ($name) {
                    case 'customers':
                        $this->uploadCustomers($filename);
                        break;
                    case 'passport':
                        $this->uploadPassports($filename);
                        break;
                    case 'zayavka':
                        $this->uploadClaims($filename);
                        break;
                    case 'uradress':
                        $this->uploadUrAddress($filename);
                        break;
                    case 'factadress':
                        $this->uploadFactAddress($filename);
                        break;
                    case 'cred':
                        $this->uploadLoans($filename);
                        break;
                    case 'dopnik':
                        $this->uploadRepayments($filename);
                        break;
                    case 'mirovoe':
                        $this->uploadPeace($filename);
                        break;
                    case 'zayavlenie_penya':
                        $this->uploadFineClaims($filename);
                        break;
                    case 'rassrochka':
                        $this->uploadSUZ($filename);
                        break;
                    case 'sverka':
                        $this->uploadSverka($filename);
                        break;
                }
                Storage::disk('local')->delete('debtors/' . $filename);
            } catch (\Exception $ex) {
                Log::error('DebtotrsInfoUploader.upload exception',['filename'=>$filename,'ex'=>$ex,'name'=>$name]);
            }
        }
        $this->refreshUploaded();
    }

    /**
     * Импорт в базу данных по заявкам должников из файла 1С
     */
    public function uploadCustomers($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                if (!mb_strlen($data[11])) {
                    continue;
                }

                $fCustomer = Customer::where('id_1c', $data[11])->first();
                if (is_null($fCustomer)) {
                    $customer = Customer::create();
                    //$customer->telephone = $data[65];
                    $customer->id_1c = $data[11];
                    $customer->save();
                } else {
                    //$fCustomer->telephone = $data[65];
                    //$fCustomer->save();
                }
            }
            fclose($handle);

            return new MyResult(true);
        }

        return new MyResult(false, 'Отсутствует файл');
    }

    /**
     * Импорт в базу данных по паспортам должников из файла 1С
     */
    public function uploadPassports($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {

                $objCustomer = Customer::where('id_1c', $data[1])->first(); // получаем кастомера по его id в 1С
                // если кастомер не найден в базе
                if (is_null($objCustomer)) {
                    $objDebtor = Debtor::where('debtor_id_1c', $data[0])->first(); // ищем должника по id в 1С
                    // если и должник не найден или у него не указан кастомер 1С, то записываем такой паспорт в табличку
                    if (is_null($objDebtor) || $objDebtor->customer_id_1c == '') {
                        $unfind = new UnfindCustomersPassports();
                        $unfind->customer_id_1c = $data[1];
                        $unfind->debtor_id_1c = $data[0];
                        $unfind->series = $data[2];
                        $unfind->number = $data[3];
                        $unfind->fio = $data[8];
                        $unfind->save();

                        continue;
                    } else { // если должник найден - создаем кастомера
                        $newCustomer = new Customer();
                        $newCustomer->id_1c = $objDebtor->customer_id_1c;
                        $newCustomer->save();
                        $customer_id = $newCustomer->id;
                    }
                } else {
                    $customer_id = $objCustomer->id;
                }

                $fPassport = Passport::where('series', $data[2])->where('number', $data[3])->first();

                if (is_null($fPassport)) {
                    $passport = new Passport();
                } else {
                    $passport = $fPassport;
                }
                try {
                    $passport->birth_date = date('Y-m-d', strtotime($data[9]));
                    $passport->birth_city = $data[10];
                    $passport->series = $data[2];
                    $passport->number = $data[3];
                    $passport->issued = $data[4];
                    $passport->issued_date = date('Y-m-d', strtotime($data[5]));
                    $passport->subdivision_code = $data[6];
                    $passport->address_reg_date = date('Y-m-d', strtotime($data[7]));
                    $passport->fio = $data[8];
                    $passport->customer_id = $customer_id;
                    $passport->save();
                } catch (\Exception $ex) {
                    Log::error('DebtorsInfoUploader.passports exception on save', ['passport' => $passport, 'data' => $data, 'ex' => $ex]);
                }
            }
            fclose($handle);

            return new MyResult(true);
        }

        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadUrAddress($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {

                $objCustomer = Customer::where('id_1c', $data[1])->first(); // получаем кастомера по id 1C
                // если кастомер не найден - пропускаем
                if (is_null($objCustomer)) {
                    continue;
                } else {
                    $customer_id = $objCustomer->id;
                }

                // если паспорт не найден (не записался в базу) - пропускаем
                $passport = Passport::where('customer_id', $customer_id)->orderBy('issued_date', 'desc')->first();
                if (is_null($passport)) {
                    continue;
                }
                
                $house = str_replace('&#34;', '"', $data[7]);
                $building = str_replace('&#34;', '"', $data[8]);
                $apartment = str_replace('&#34;', '"', $data[9]);
                
                $passport->zip = $data[2];
                $passport->address_region = $data[3];
                $passport->address_district = $data[4];
                $passport->address_city = $data[5];
                $passport->address_street = $data[6];
                $passport->address_house = $house;
                $passport->address_building = $building;
                $passport->address_apartment = $apartment;
                $passport->address_city1 = $data[10];
                $passport->save();
            }
            fclose($handle);
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }
    
    public function uploadFactAddress($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {

                $objCustomer = Customer::where('id_1c', $data[1])->first();

                if (is_null($objCustomer)) {
                    continue;
                } else {
                    $customer_id = $objCustomer->id;
                }

                $passport = Passport::where('customer_id', $customer_id)->orderBy('issued_date', 'desc')->first();
                if (is_null($passport)) {
                    continue;
                }
                
                $house = str_replace('&#34;', '"', $data[7]);
                $building = str_replace('&#34;', '"', $data[8]);
                $apartment = str_replace('&#34;', '"', $data[9]);
                
                $passport->fact_zip = $data[2];
                $passport->fact_address_region = $data[3];
                $passport->fact_address_district = $data[4];
                $passport->fact_address_city = $data[5];
                $passport->fact_address_street = $data[6];
                $passport->fact_address_house = $house;
                $passport->fact_address_building = $building;
                $passport->fact_address_apartment = $apartment;
                $passport->fact_address_city1 = $data[10];
                $passport->save();
            }
            fclose($handle);
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadClaims($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $customer = Customer::where('id_1c', $data[11])->first();

                if (is_null($customer)) {
                    continue;
                }

                $passport = Passport::where('customer_id', $customer->id)->first();
                if (is_null($passport)) {
                    continue;
                }

                $fClaim = Claim::where('id_1c', $data[7])->first();
                if (is_null($fClaim)) {
                    $claim = new Claim();
                } else {
                    $claim = $fClaim;
                }

                $fAbout = about_client::where('customer_id', $customer->id)->first();
                if (is_null($fAbout)) {
                    $about = new about_client();
                } else {
                    $about = $fAbout;
                }

                /* if (is_null($claim)) {
                  $claim = new Claim();
                  $about = new about_client();
                  } else {
                  $about = about_client::where('customer_id', $customer->id)->first();
                  if (is_null($about)) {
                  $about = new about_client();
                  }
                  } */

//                $about = new about_client();
                $about->customer_id = $customer->id;
                $about->sex = $data[12];
                $about->goal = $data[13];
                if (empty($data[14])) {
                    $data[14] = 1;
                }
                $about->zhusl = $data[14];
                $about->deti = $data[15];
                $about->fiosuprugi = $data[16];
                $about->fioizmena = $data[17];
                $about->avto = $data[18];
                $about->telephonehome = $data[19];
                $about->organizacia = $data[20];
                $about->innorganizacia = $data[21];
                $about->dolznost = $data[22];
                if ($data[23] != '') {
                    $about->vidtruda = $data[23];
                }
                $about->fiorukovoditel = $data[24];
                $about->adresorganiz = $data[25];
                $about->telephoneorganiz = $data[26];
                $about->credit = $data[27];
                $about->dohod = $data[28];
                $about->dopdohod = $data[29];
                $about->stazlet = $data[30];
                if (empty($data[31])) {
                    $data[31] = 1;
                }
                $about->adsource = $data[31];
                $about->pensionnoeudost = $data[32];
                $about->telephonerodstv = $data[33];
                if (empty($data[34])) {
                    $data[34] = 1;
                }
                $about->stepenrodstv = $data[34];
                if (empty($data[35])) {
                    $data[35] = 1;
                }
                $about->obrasovanie = $data[35];
                $about->pensioner = $data[36];
                $about->postclient = $data[37];
                $about->armia = $data[38];
                $about->poruchitelstvo = $data[39];
                $about->zarplatcard = $data[40];
                $about->alco = $data[41];
                $about->drugs = $data[42];
                $about->stupid = $data[43];
                $about->badspeak = $data[44];
                $about->pressure = $data[45];
                $about->dirty = $data[46];
                $about->smell = $data[47];
                $about->badbehaviour = $data[48];
                $about->soldier = $data[49];
                $about->other = $data[50];
                $about->watch = $data[51];
                $about->anothertelephone = $data[52];
                if (empty($data[53])) {
                    $data[53] = 1;
                }
                $about->marital_type_id = $data[53];
                $about->recomend_phone_1 = $data[53];
                $about->recomend_phone_2 = $data[54];
                $about->recomend_phone_3 = $data[55];
                $about->recomend_fio_1 = $data[56];
                $about->recomend_fio_2 = $data[57];
                $about->recomend_fio_3 = $data[58];
                $about->other_mfo = $data[59];
                $about->other_mfo_why = $data[60];
                $about->dohod_husband = $data[67];
                $about->pension = $data[68];
                $about->save();

                $user = User::where('id_1c', $data[4])->first();
                if (is_null($user)) {
                    $user_id = 18;
                } else {
                    $user_id = $user->id;
                }

                $subdivision = Subdivision::where('name_id', $data[6])->first();
                if (is_null($subdivision)) {
                    $subdivision_id = 113;
                } else {
                    $subdivision_id = $subdivision->id;
                }

//                $claim = new Claim();
                $claim->customer_id = $customer->id;
                $claim->srok = $data[1];
                $claim->summa = $data[2];
                $claim->date = $data[3];
                $claim->created_at = date('Y-m-d H:i:s', strtotime($data[3]));
                $claim->comment = $data[66];
                $claim->status = $data[5];
                $claim->user_id = $user_id;
                $claim->subdivision_id = $subdivision_id;
                $claim->id_1c = $data[7];
                $claim->max_money = $data[8];
                if ($data[9] == '') {
                    $data[9] = 0;
                }
                $claim->uki = $data[9];
                $claim->id_teleport = $data[10];
                $claim->about_client_id = $about->id;
                $claim->passport_id = $passport->id;
                $claim->save();
            }
            fclose($handle);
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadLoans($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $claim = Claim::where('id_1c', $data[5])->first();
                Log::info('DebtorInfoUploader.uploadLoans get claim', ['data' => $data, 'claim_id_1c' => $data[5]]);
                if (is_null($claim) && !empty($data[5])) {
                    Log::info('DebtorInfoUploader.uploadLoans claim is null', ['data' => $data, 'claim_id_1c' => $data[5]]);
                    continue;
                }
                //если уже есть кредитник ссылающийся на ту же заявку то пока пропустить
                if (!is_null($claim)) {
                    $armf_claim = DB::Table('armf.claims')->select(DB::raw('*'))->where('id_1c', $claim->id_1c)->first();
                    if (!is_null($armf_claim) && $armf_claim->multi_loan == 0) {
                        $armf_loan = DB::Table('armf.loans')->select(DB::raw('*'))->where('id_1c', $data[3])->first();
                        
                        if (!is_null($armf_loan) && $armf_loan->multi_dop == 0) {
                            $loanWithClaim = Loan::where('claim_id', $claim->id)->where('true_id_1c', '<>', $data[2])->first();
                            if (!is_null($loanWithClaim)) {
                                Log::info('DebtorInfoUploader.uploadLoans duplicate loan', ['claim' => $claim->toArray(), 'loan' => $loanWithClaim->toArray(), 'id_1c' => $data[3]]);
                                continue;
                            }
                        }
                    }
                }

                if (empty($data[8])) {
                    $loantype_id = 13;
                } else {
                    $loantype = LoanType::where('id_1c', $data[8])->first();
                    if (is_null($loantype)) {
                        Log::info('DebtorInfoUploader.uploadLoans loantype is null', ['data' => $data]);
                        continue;
                    }

                    $loantype_id = $loantype->id;
                }

                $card_id = false;
                if ($data[9] != '') {
                    $card = Card::where('card_number', $data[9])->first();
                    if (is_null($card)) {
                        if (!is_null($claim->customer_id && $claim->customer_id != '')) {
                            $crCard = Card::create(array('card_number' => $data[9], 'secret_word' => $data[10], 'customer_id' => $claim->customer_id));
                            $card_id = $crCard->id;
                        }
                    } else {
                        $card_id = $card->id;
                    }
                }
                
                $subdivision = Subdivision::where('name_id', $data[11])->first();
                if (is_null($subdivision)) {
                    $armf_subdivision = DB::Table('armf.subdivisions')->select(DB::raw('*'))->where('name_id', $data[11])->first();
                    if (!is_null($armf_subdivision)) {
                        $armf_subdivision = json_decode(json_encode($armf_subdivision), true);
                        $subdivision = new Subdivision();
                        $subdivision->fill($armf_subdivision);
                        $subdivision->save();
                        
                        $subdivision_id = $subdivision->id;
                    } else {
                        $subdivision_id = 113;
                    }
                    
                } else {
                    $subdivision_id = $subdivision->id;
                }

                $user = User::where('id_1c', $data[12])->first();
                if (is_null($user)) {
                    $armf_user = DB::Table('armf.users')->select(DB::raw('*'))->where('id_1c', $data[12])->first();
                    if (!is_null($armf_user)) {
                        $user = User::where('login', $armf_user->login)->first();
                        if (!is_null($user)) {
                            $user->id_1c = $armf_user->id_1c;
                            $user->save();
                        } else {
                            $armf_user = json_decode(json_encode($armf_user), true);
                            
                            $tmp_subdivision = DB::Table('armf.subdivisions')->select(DB::raw('*'))->where('id', $armf_user['subdivision_id'])->first();
                            
                            Log::info('DebtorInfoUploader.uploadLoans armf_user tmp', ['user' => $armf_user]);
                            
                            $tmp_debtor_subdivision = Subdivision::where('name_id', $tmp_subdivision->name_id)->first();
                            if (is_null($tmp_debtor_subdivision)) {
                                $tmp_subdivision = json_decode(json_encode($tmp_subdivision), true);
                                $tmp_debtor_subdivision = new Subdivision();
                                $tmp_debtor_subdivision->fill($tmp_subdivision);
                                $tmp_debtor_subdivision->save();
                                
                                $armf_user['subdivision_id'] = $tmp_debtor_subdivision->id;
                            }
                            
                            $armf_user['subdivision_id'] = $tmp_debtor_subdivision->id;
                            
                            $user = new User();
                            $user->fill($armf_user);
                            $user->save();
                        }
                        
                        $user_id = $user->id;
                    } else {
                        $user_id = 18;
                    }
                } else {
                    $user_id = $user->id;
                }

                $promocode_id = false;
                if ($data[13] != '') {
                    $promocode = Promocode::where('number', $data[13])->first();
                    if (!is_null($promocode)) {
                        $promocode_id = $promocode->id;
                    }
                }

                if (empty($data[5])) {
                    $claim = new Claim();
                    $claim->summa = $data[6];
                    $claim->id_1c = $data[3];
                    $claim->srok = $data[7];
                    $debtor = Debtor::where('loan_id_1c', $data[3])->first();
                    if (!is_null($debtor)) {
                        $passport = Passport::getBySeriesAndNumber($debtor->passport_series, $debtor->passport_number);
                    }
                    if (isset($passport) && !is_null($passport)) {
                        try {
                            $claim->passport_id = $passport->id;
                            $customer = $passport->customer;
                            if (is_null($customer)) {
                                Log::info('DebtorInfoUploader.uploadLoans no customer', ['data' => $data, 'passport' => $passport->toArray()]);
                                continue;
                            }
                            $ac = about_client::where('customer_id', $customer->id)->first();
                            if (is_null($ac)) {
                                $ac = new about_client();
                                $ac->customer_id = $customer->id;
                                $ac->save();
                            }
                            $claim->about_client_id = $ac->id;
                            $claim->user_id = $user_id;
                            $claim->subdivision_id = $subdivision_id;
                            $claim->save();
                        } catch (\Exception $ex) {
                            Log::info('DebtorInfoUploader.uploadLoans no claim in loan', ['data' => $data, 'passport' => $passport->toArray()]);
                            continue;
                        }
                    } else {
                        Log::info('DebtorInfoUploader.uploadLoans no passport', ['data' => $data]);
                        continue;
                    }
                }

                $loan = Loan::where('true_id_1c', $data[2])->first();
                if (is_null($loan)) {
                    $loan = new Loan();
                }
                $loan->money = $data[6];
                $loan->time = $data[7];
                $loan->claim_id = $claim->id;
                $loan->loantype_id = $loantype_id;
                $loan->created_at = date('Y-m-d H:i:s', strtotime($data[4]));
                $loan->id_1c = $data[3];
                $loan->true_id_1c = $data[2];
                if ($card_id) {
                    $loan->card_id = $card_id;
                    $loan->in_cash = 0;
                } else {
                    $loan->in_cash = 1;
                }
                $loan->closed = 0;
                $loan->subdivision_id = $subdivision_id;
                $loan->user_id = $user_id;
                $loan->uki = $data[16];
                if (!empty($data[15])) { //tranche_number
                    $loan->tranche_number = $data[15];
                }
                if (!empty($data[14])) { //special_percent
                    $loan->special_percent = $data[14];
                }
                if ($promocode_id) {
                    $loan->promocode_id = $promocode_id;
                }
                $loan->save();
                Log::info('DebtorInfoUploader.uploadLoans loan saved', ['loan' => $loan, 'data' => $data]);
            }
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadRepayments($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $loan = Loan::getById1cAndCustomerId1c2($data[2], $data[1]);
                if (is_null($loan)) {
                    continue;
                }
                $rep = Repayment::where('id_1c', $data[10])->first();
                if (is_null($rep)) {
                    $rep = new Repayment();
                }


                $repDate = new Carbon($data[4]);
                $data[4] = with($repDate)->format('Y-m-d H:i:s');

                $cardNalDopDay = new Carbon(config('options.card_nal_dop_day'));

                if ($repDate->gte(new Carbon(config('options.perm_new_rules_day')))) {
                    $prevRep = Repayment::where('loan_id', $loan->id)->where('id_1c', '<>', $data[3])->orderBy('created_at', 'desc')->first();
                    $otherReps = Repayment::where('loan_id', $loan->id)->where('id_1c', '<>', $data[3])->get();
//                    \PC::debug($otherReps, 'otherexpreps');
                    $hasOverdue = false;
                    if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                        foreach ($otherReps as $otherRep) {
//                            \PC::debug($otherRep->getOverdueDays(true),$otherRep->id_1c);
                            if ($otherRep->getOverdueDays(true) > 0) {
//                                \PC::debug('overdue');
                                $hasOverdue = true;
                            }
                        }
                    }
                    /**
                     * Здесь меняем тип допника в зависимости от условий
                     * хозяйке на заметку: в Loan.getRequiredMoneyDetails() тип договора меняется на просроченный в зависимости от пришедшего процента
                     */
                    if (!is_null($prevRep)) {
                        if (($repDate->gt(with(new Carbon($prevRep->created_at))->addDays($prevRep->time + 1)) || $hasOverdue) &&
                                $repDate->gt(new Carbon('2016-06-07')) &&
                                $loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                            if ($loan->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik7'))->select('id')->first())->id;
                            } else {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik5'))->select('id')->first())->id;
                            }
                        } else {
                            if ($repDate->gte($cardNalDopDay)) {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik6'))->select('id')->first())->id;
                            } else {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik4'))->select('id')->first())->id;
                            }
                        }
                    } else if ($repDate->gt(with(new Carbon($loan->created_at))->addDays($loan->time + 1)) &&
                            $repDate->gt(new Carbon('2016-06-07')) &&
                            $loan->created_at->gte(new Carbon(config('options.new_rules_day'))) || $hasOverdue) {
                        if ($loan->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik7'))->select('id')->first())->id;
                        } else {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik5'))->select('id')->first())->id;
                        }
                    } else {
                        if ($repDate->gte($cardNalDopDay)) {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik6'))->select('id')->first())->id;
                        } else {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik4'))->select('id')->first())->id;
                        }
                    }
                } else if (with(new Carbon($loan->created_at))->gte(new Carbon(config('options.new_rules_day')))) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik2'))->select('id')->first())->id;
                } else {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik3'))->select('id')->first())->id;
                }

                $subdivision = Subdivision::where('name_id', $data[15])->first();
                if (is_null($subdivision)) {
                    $subdivision_id = 113;
                } else {
                    $subdivision_id = $subdivision->id;
                }

                $user = User::where('id_1c', $data[16])->first();
                if (is_null($user)) {
                    $user_id = 18;
                } else {
                    $user_id = $user->id;
                }

                $data[7] = empty($data[7]) ? 0 : $data[7];
                $data[8] = empty($data[8]) ? 0 : $data[8];
                $data[11] = empty($data[7]) ? 0 : $data[11];
                $data[12] = empty($data[8]) ? 0 : $data[12];
                $data[13] = empty($data[7]) ? 0 : $data[13];
                $data[14] = empty($data[8]) ? 0 : $data[14];

                $rep->loan_id = $loan->id;
                $rep->created_at = $data[4];
                $rep->time = $data[5];
                $rep->fine = $data[6] * 100;
                $rep->exp_pc = 0;
                $rep->pc = 0;
                $rep->od = $data[7] * 100;
                $rep->paid_money = $data[8] * 100;
                $rep->discount = $data[9];
                $rep->id_1c = $data[10];
                $rep->was_pc = $data[11] * 100;
                $rep->was_exp_pc = $data[12] * 100;
                $rep->was_od = $data[13] * 100;
                $rep->was_fine = $data[14] * 100;
                $rep->subdivision_id = $subdivision_id;
                $rep->user_id = $user_id;
                $rep->comment = $data[17];
                $rep->tax = 0;
                $rep->was_tax = 0;
                $rep->save();
            }
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadPeace($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $loan = Loan::getById1cAndCustomerId1c2($data[2], $data[1]);
                if (is_null($loan)) {
                    continue;
                }

                $rep = Repayment::where('id_1c', $data[3])->first();
                if (is_null($rep)) {
                    $rep = new Repayment();
                }

                $peace_type = (int) $data[14];
                if ($peace_type == 0) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace'))->select('id')->first())->id;
                } else if ($peace_type == 1) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace2'))->select('id')->first())->id;
                } else if ($peace_type == 2) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace3'))->select('id')->first())->id;
                } else if ($peace_type == 3) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace4'))->select('id')->first())->id;
                }

                $subdivision = Subdivision::where('name_id', $data[15])->first();
                if (is_null($subdivision)) {
                    $subdivision_id = 113;
                } else {
                    $subdivision_id = $subdivision->id;
                }

                $user = User::where('id_1c', $data[16])->first();
                if (is_null($user)) {
                    $user_id = 18;
                } else {
                    $user_id = $user->id;
                }

                $repDate = new Carbon($data[4]);
                $data[4] = with($repDate)->format('Y-m-d H:i:s');

                $data[6] = empty($data[6]) ? 0 : $data[6];
                $data[7] = empty($data[7]) ? 0 : $data[7];
                $data[8] = empty($data[8]) ? 0 : $data[8];
                $data[9] = empty($data[9]) ? 0 : $data[9];
                $data[10] = empty($data[10]) ? 0 : $data[10];
                $data[11] = empty($data[11]) ? 0 : $data[11];
                $data[12] = empty($data[12]) ? 0 : $data[12];
                $data[13] = empty($data[13]) ? 0 : $data[13];

                $rep->loan_id = $loan->id;
                $rep->id_1c = $data[3];
                $rep->created_at = $data[4];
                $rep->time = $data[5];
                $rep->pc = $data[6] * 100;
                $rep->exp_pc = $data[7] * 100;
                $rep->fine = $data[8] * 100;
                $rep->od = $data[9] * 100;
                $rep->was_pc = $data[10] * 100;
                $rep->was_exp_pc = $data[11] * 100;
                $rep->was_od = $data[12] * 100;
                $rep->was_fine = $data[13] * 100;
                $rep->comment = $data[17];
                $rep->subdivision_id = $subdivision_id;
                $rep->user_id = $user_id;
                $rep->save();

                if (mb_strlen($data[18]) > 0) {
                    $arPays = explode(';', $data[18]);
                    $total_fields = count($arPays) - 1;
                    $iterations = floor($total_fields / 5);

                    for ($i = 0; $i < $iterations; $i++) {
                        $m = $i * 5;
                        $pay = new PeacePay();
                        $pay->end_date = date('Y-m-d', strtotime($arPays[$m]));
                        $pay->repayment_id = $rep->id;
                        $pay->exp_pc = $arPays[$m + 3] * 100;
                        $pay->fine = $arPays[$m + 4] * 100;
                        $pay->money = $arPays[$m + 1] * 100;
                        $pay->total = $arPays[$m + 1] * 100;
                        $pay->closed = $arPays[$m + 2];
                        $pay->save();
                    }
                }
            }
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadFineClaims($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $loan = Loan::getById1cAndCustomerId1c2($data[2], $data[1]);
                if (is_null($loan)) {
                    continue;
                }

                $rep = Repayment::where('id_1c', $data[3])->first();
                if (is_null($rep)) {
                    $rep = new Repayment();
                }

                if ($data[10] == 1) {
                    //$rt = RepaymentType::where('text_id', config('options.rtype_claim2'))->select('id')->first();
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim2'))->select('id')->first())->id;
                } else if ($data[10] == 2) {
                    //допник с комиссией
                    //$rt = RepaymentType::where('text_id', config('options.rtype_claim3'))->select('id')->first();
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim3'))->select('id')->first())->id;
                } else {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim'))->select('id')->first())->id;
                }

                $subdivision = Subdivision::where('name_id', $data[11])->first();
                if (is_null($subdivision)) {
                    $subdivision_id = 113;
                } else {
                    $subdivision_id = $subdivision->id;
                }

                $user = User::where('id_1c', $data[12])->first();
                if (is_null($user)) {
                    $user_id = 18;
                } else {
                    $user_id = $user->id;
                }

                $data[6] = empty($data[6]) ? 0 : $data[6];
                $data[7] = empty($data[7]) ? 0 : $data[7];
                $data[8] = empty($data[8]) ? 0 : $data[8];
                $data[9] = empty($data[9]) ? 0 : $data[9];

                $rep->loan_id = $loan->id;
                $rep->id_1c = $data[3];
                $rep->created_at = date('Y-m-d H:i:s', strtotime($data[4]));
                $rep->time = $data[5];
                $rep->pc = $data[6] * 100;
                $rep->exp_pc = $data[7] * 100;
                $rep->fine = $data[8] * 100;
                $rep->od = $data[9] * 100;
                $rep->comment = $data[13];
                $rep->subdivision_id = $subdivision_id;
                $rep->user_id = $user_id;

                $rep->save();
            }
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function uploadSUZ($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $loan = Loan::getById1cAndCustomerId1c2($data[2], $data[1]);
                if (is_null($loan)) {
                    continue;
                }

                $rep = Repayment::where('id_1c', $data[3])->first();
                if (is_null($rep)) {
                    $rep = new Repayment();
                }

                $repType = ($loan->created_at->lt(new Carbon('2014-07-01'
                                . ''))) ? "suz1" : "suz2";

                $rep->repayment_type_id = RepaymentType::where('text_id', config('options.rtype_' . $repType))->value('id');

                $user = User::where('id_1c', $data[5])->first();
                if (is_null($user)) {
                    $user_id = 18;
                } else {
                    $user_id = $user->id;
                }

                $data[6] = empty($data[6]) ? 0 : $data[6];
                $data[7] = empty($data[7]) ? 0 : $data[7];
                $data[8] = empty($data[8]) ? 0 : $data[8];
                $data[9] = empty($data[9]) ? 0 : $data[9];
                $data[10] = empty($data[10]) ? 0 : $data[10];

                $rep->loan_id = $loan->id;
                $rep->id_1c = $data[3];
                $rep->created_at = date('Y-m-d H:i:s', strtotime($data[4]));
                $rep->subdivision_id = 113;
                $rep->user_id = $user_id;
                $rep->pc = $data[6] * 100;
                $rep->exp_pc = $data[7] * 100;
                $rep->fine = $data[8] * 100;
                $rep->od = $data[9] * 100;
                $rep->tax = $data[10] * 100;

                if (!empty($data[11])) {
                    $arData = [];
                    $arData['stock_type'] = $data[11];
                    $arData['stock_created_at'] = $data[12];

                    $arAllPays = explode(';', $data[13]);
                    $total_fields = count($arAllPays) - 1;
                    $iterations = floor($total_fields / 3);
                    $arPays = [];
                    for ($i = 0; $i < $iterations; $i++) {
                        $m = $i * 3;
                        $arPays[$i]['date'] = $arAllPays[$m];
                        $arPays[$i]['total'] = $arAllPays[$m + 1];
                    }

                    $arData['pays'] = $arPays;
                    $arData['print_od'] = $data[9] * 100;
                    $arData['print_pc'] = $data[6] * 100;
                    $arData['print_exp_pc'] = $data[7] * 100;
                    $arData['print_fine'] = $data[8] * 100;
                    $arData['print_tax'] = $data[10] * 100;

                    $rep->data = json_encode($arData);
                }

                $rep->save();
            }
            return new MyResult(true);
        }
        return new MyResult(false, 'Отсутствует файл');
    }

    public function refreshUploaded() {
        $debtors = Debtor::where('uploaded', 0)->get();
        foreach ($debtors as $debtor) {
            $passport = Passport::where('series', $debtor->passport_series)
                    ->where('number', $debtor->passport_number)
                    ->first();

            $loan = Loan::where('id_1c', $debtor->loan_id_1c)->first();

            if (!is_null($passport) && !is_null($loan)) {
                $debtor->uploaded = 1;
                $debtor->save();
            }
        }
        return 1;
    }

    public function uploadSverka($filename) {
        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $debtor = Debtor::where('debtor_id_1c', $data[0])->first();
                if (is_null($debtor)) {
                    continue;
                }

                $debtor->is_debtor = $data[1];
                if ($data[1] == 0) {
                    $debtor->od = 0;
                    $debtor->pc = 0;
                    $debtor->exp_pc = 0;
                    $debtor->fine = 0;
                    $debtor->sum_indebt = 0;
                }

                $base = trim($data[3]);
                $debt_group_id = ($data[4] == 'NULL') ? null : $data[4];
                $str_podr = trim($data[5]);

                $debtor->responsible_user_id_1c = $data[2];
                $debtor->base = $base;
                $debtor->debt_group_id = $debt_group_id;
                $debtor->str_podr = $str_podr;
                $debtor->decommissioned = $data[6];

                $debtor->save();
            }
        }

        return 1;
    }

    public function clearOtherPhones() {
        DebtorsOtherPhones::truncate();
        return 1;
    }

    public function updateClientInfo($filename) {
        $this->copyFileFromFtp($filename);

        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                if (!empty($data[9])) {
                    $domPhone = preg_replace("/[^0-9]/", '', $data[9]);
                    if (!empty($domPhone)) {
                        DebtorsOtherPhones::addRecord($data[0], $domPhone, 1);
                    }
                }

                if (!empty($data[16])) {
                    $workPhone = preg_replace("/[^0-9]/", '', $data[16]);
                    if (!empty($workPhone)) {
                        DebtorsOtherPhones::addRecord($data[0], $workPhone, 2);
                    }
                }

                if (!empty($data[22])) {
                    $relativePhone = preg_replace("/[^0-9]/", '', $data[22]);
                    if (!empty($relativePhone)) {
                        DebtorsOtherPhones::addRecord($data[0], $relativePhone, 3);
                    }
                }

                if (!empty($data[24])) {
                    $anotherPhone = preg_replace("/[^0-9]/", '', $data[24]);
                    if (!empty($anotherPhone)) {
                        DebtorsOtherPhones::addRecord($data[0], $anotherPhone, 4);
                    }
                }

                $customer = Customer::where('id_1c', $data[1])->first();
                if (is_null($customer)) {
                    Log::error('updateClientInfo error customer not found: ', ['customer_id_1c' => $data[1]]);
                    continue;
                }

                if ($customer->telephone != $data[27]) {
                    $customer->telephone = $data[27];
                    $customer->save();

                    Log::info('updateClientInfo customer telephone updated: ', ['customer_id_1c' => $data[1]]);
                }

                $passport = Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
                $about_client = about_client::where('customer_id', $customer->id)->first();

                if (!is_null($passport)) {
                    $passport->zip = $data[31];
                    $passport->address_region = $data[32];
                    $passport->address_district = $data[33];
                    $passport->address_city = $data[34];
                    $passport->address_street = $data[35];
                    $passport->address_house = $data[36];
                    $passport->address_building = $data[37];
                    $passport->address_apartment = $data[38];
                    $passport->address_city1 = $data[39];
                    $passport->fact_zip = $data[40];
                    $passport->fact_address_region = $data[41];
                    $passport->fact_address_district = $data[42];
                    $passport->fact_address_city = $data[43];
                    $passport->fact_address_street = $data[44];
                    $passport->fact_address_house = $data[45];
                    $passport->fact_address_building = $data[46];
                    $passport->fact_address_apartment = $data[47];
                    $passport->fact_address_city1 = $data[48];

                    //$passport->birth_date = $data[26];

                    $passport->save();
                } else {
                    Log::error('updateClientInfo error passport not found: ', ['customer_id_1c' => $data[1]]);
                }

                if (!is_null($about_client)) {
                    $about_client->goal = $data[3];
                    $about_client->zhusl = $data[4];
                    $about_client->deti = $data[5];
                    $about_client->fiosuprugi = $data[6];
                    $about_client->fioizmena = $data[7];
                    $about_client->avto = $data[8];
                    $about_client->telephonehome = $data[9];
                    $about_client->organizacia = $data[10];
                    $about_client->innorganizacia = $data[11];
                    $about_client->dolznost = $data[12];
                    $about_client->vidtruda = $data[13];
                    $about_client->fiorukovoditel = $data[14];
                    $about_client->adresorganiz = $data[15];
                    $about_client->telephoneorganiz = $data[16];
                    $about_client->credit = $data[17];
                    $about_client->dohod = $data[18];
                    $about_client->dopdohod = $data[19];
                    $about_client->stazlet = $data[20];
                    $about_client->pensionnoeudost = $data[21];
                    $about_client->telephonerodstv = $data[22];
                    $about_client->stepenrodstv = $data[23];
                    $about_client->anothertelephone = $data[24];
                    $about_client->pensioner = $data[25];
                    $about_client->comment = $data[28];
                    $about_client->dohod_muzha = $data[29];
                    $about_client->pensia = $data[30];
                } else {
                    Log::error('updateClientInfo error about_client not found: ', ['customer_id_1c' => $data[1]]);
                }
            }
            fclose($handle);

            return 1;
        }

        return 0;
    }

}
