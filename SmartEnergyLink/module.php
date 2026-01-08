<?php

declare(strict_types=1);
	class SmartEnergyLink extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			// Propertys
			$this->RegisterPropertyString("LoginMail", "");
			$this->RegisterPropertyString("Password", "");
			$this->RegisterPropertyString("Start_Year", "");
			$this->RegisterPropertyString("Start_Month", "");
			$this->RegisterPropertyString("Start_Day", "");
			
			// Timer
			$this->RegisterTimer("GET_CONTENT", 900000, "DC_GetEnergySummary();");

			// Variable
			$this->RegisterVariableFloat("ENERGY_SUMMARY", $this->Translate("Energy consumption total:"), "", 0);
			$this->RegisterVariableFloat("SOLAR_ENERGY_TOTAL", $this->Translate("Solar energy total"), "", 1);
			$this->RegisterVariableFloat("SOLAR_ENERGY_TOTAL_PERCENT", $this->Translate("Percentage share of solar energy total"), "", 2);
			$this->RegisterVariableFloat("ENERGY_DAY", $this->Translate("Energy consumption today:"), "", 3);
			$this->RegisterVariableFloat("SOLAR_ENERGY_DAY", $this->Translate("Solar energy today"), "", 4);
			$this->RegisterVariableFloat("SOLAR_ENERGY_TODAY_PERCENT", $this->Translate("Percentage share of solar energy today"), "", 5);
			// $this->RegisterVariableString("ENERGY_LAST_15_MIN", $this->Translate("Energy consumption last 15 min.:"), "", 0);
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		private function Login() {
			$eMail = $this->ReadPropertyString("LoginMail");
			$password = $this->ReadPropertyString("Password");
			$postfields = json_encode(["email" => $eMail, "password" => $password]);

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL => "https://portal.sel.energy/api/v1/auth/login/",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $postfields,
				CURLOPT_HTTPHEADER => [
					"Content-Type: application/json",
				],
			]);

			$response = curl_exec($curl);
			$err = curl_error($curl);
		
			
			if($err) {
				print_r("Fetch error: " . $err);
			} else {
				$jsonResponse = json_decode($response, true);
			}
			IPS_LogMessage("Smart Energy Link", "Token: " . $jsonResponse["access"]);
			return $jsonResponse["access"];
		}

		private function GetContent(string $link) {

			$auth = $this->Login();

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL => "https://portal.sel.energy/api/v1/" . $link,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => [
					"Content-Type: application/json",
					$auth,
				],
			]);

			$response = curl_exec($curl);
			$err = curl_error($curl);
		
			
			if($err) {
				print_r("Fetch error: " . $err);
			} else {
				$jsonResponse = json_decode($response, true);
			}
			IPS_LogMessage("Smart Energy Link", $jsonResponse);
			return $jsonResponse;
		}

		public function GetEnergySummary() {
			$startYear = $this->ReadPropertyString("Start_Year");
			$startMonth = $this->ReadPropertyString("Start_Month");
			$startDay = $this->ReadPropertyString("Start_Day");

			$currentYear = date("Y");
			$currentMonth = date("m");
			$currentDay = date("d");
			$energySummary = $this->GetContent("sel/member-autarchy-electricity/?from={$startYear}-{$startMonth}-{$startDay}&to={$currentYear}-{$currentMonth}-{$currentDay}&ts_format=ms&community=548");
			$this->SetValue("ENERGY_SUMMARY", $energySummary["summary"]["wattHours"]);
			$this->SetValue("SOLAR_ENERGY_TOTAL", $energySummary["summary"]["ownConsumption"]);
			$this->SetValue("SOLAR_ENERGY_TOTAL_PERCENT", $energySummary["summary"]["autarchy"]);
			
			$energyToday = $this->GetContent("sel/member-autarchy-electricity/?from={$currentYear}-{$currentMonth}-{$currentDay}&to={$currentYear}-{$currentMonth}-{$currentDay}&ts_format=ms&community=548");
			$this->SetValue("ENERGY_TODAY", $energyToday["summary"]["wattHours"]);
			$this->SetValue("SOLAR_ENERGY_TODAY", $energySummary["summary"]["ownConsumption"]);
			$this->SetValue("SOLAR_ENERGY_TODAY_PERCENT", $energySummary["summary"]["autarchy"]);
		}
	}