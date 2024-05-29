<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Mail\NotificationAppoint;
use Illuminate\Support\Facades\Mail;
use App\Models\Appointment\Appointment;

class NotificationAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificar al paciente 1 hora antes de su cita medica, por medio de correo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        date_default_timezone_set("America/El_Salvador");
        $simulet_hour_number = date(now()->format("Y-m-d")); //strtotime(date("2024-05-29 15:10:00"));  //
        //error_log($simulet_hour_number);
        $appointments = Appointment::whereDate("date_appointment","2024-05-29")//now()->format("Y-m-d")
                                    ->where("status",1)
                                   // ->where("cron_state",1)
                                    ->get();


        /*
         $simulet_hour_number = date("2024-05-29 15:00:15"); //strtotime(date("2024-05-29 15:10:00"));  //
        $appointments = Appointment::whereDate("date_appointment","2024-05-29")//now()->format("Y-m-d")
                                    ->where("status",1)
                                   // ->where("cron_state",1)
                                    ->get();
        */

        $now_time_number = strtotime($simulet_hour_number);//now()->format("Y-m-d h:i:s");//
        //dd($now_time_number);

        $patients = collect([]);
       // error_log("Antes de entrar aqui");
        foreach ($appointments as $key => $appointment) {
            //error_log("Entro aqui");
            $hour_start = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hour_end = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;
            error_log($hour_start.' ---- '.$hour_end.' ---- '.$simulet_hour_number);
            // 2023-10-25 08:30:00 -> 2023-10-25 07:30:00
            $hour_start = strtotime(Carbon::parse($simulet_hour_number." ".$hour_start)->subHour());
            $hour_end = strtotime(Carbon::parse($simulet_hour_number." ".$hour_end)->subHour());

           error_log($hour_start.' ---- '.$hour_end.' ---- '.$simulet_hour_number);
            error_log($hour_start.' ---- '.$hour_end.' ---- '. $now_time_number);

            if($hour_start <= $now_time_number && $hour_end >= $now_time_number){
                error_log($appointment->avatar);
                $patients->push([
                    "name" => $appointment->patient->name,
                    "surname" => $appointment->patient->surname,
                    "avatar" => $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : NULL,
                    "email" => $appointment->patient->email,
                    "mobile" => $appointment->patient->mobile,
                    "specialitie_name" => $appointment->specialitie->name,
                    "n_document" => $appointment->patient->n_document,
                    "hour_start_format" => Carbon::parse(date("Y-m-d")." ".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                    "hour_end_format" => Carbon::parse(date("Y-m-d")." ".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                ]);
            }
            //$appointment->update(["cron_state" => 2]);
        }
        foreach ($patients as $key => $patient) {
            Mail::to($patient["email"])->send(new NotificationAppoint($patient));
        }

        //dd($patients);

    }
}
