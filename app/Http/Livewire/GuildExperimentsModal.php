<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use lastguest\Murmur;
use Livewire\Component;

class GuildExperimentsModal extends Component
{

    public $guildId = "";
    public $guildName = "";
    public $features = [];
    public $experiments = [];

    protected $listeners = ['update'];

    public function update($guildId, $guildName, $features) {

        $this->reset();

        $this->guildId = $guildId;
        $this->guildName = urldecode($guildName);
        $this->features = json_decode($features);

        $experimentsJson = [];
        if(Cache::has('experimentsJson')) {
            $experimentsJson = Cache::get('experimentsJson');
        }else{
            $response = Http::get('https://experiments.fbrettnich.workers.dev/');
            if($response->ok()) {
                $experimentsJson = $response->json();
                Cache::put('experimentsJson', $experimentsJson, 900); // 15 minutes
            }
        }

        $allExperiments = [];
        foreach ($experimentsJson as $entry) {
            if($entry['type'] == "guild" && !empty($entry['rollout'])) {
                array_push($allExperiments, $entry);
            }
        }

        foreach ($allExperiments as $experiment) {
            $murmurhash = Murmur::hash3_int($experiment['id'] . ':' . $this->guildId);
            $murmurhash = $murmurhash % 10000;

            $treatments = [];
            $filters = [];
            foreach ($experiment['rollout'][3] as $population) {

                $filterPassed = true;

                foreach ($population[1] as $filter) {
                    switch ($filter[0]) {
                        case 1604612045: // Feature
                            foreach ($filter[1][0][1] as $popfilter) {
                                if(!in_array($popfilter, $this->features)) {
                                    $filterPassed = false;
                                }
                            }
                            break;
                        case 2918402255: // MemberCount
                            array_push($filters, "(Only if server member count is " . ($filter[1][1][1] ? ("in range " . ($filter[1][0][1] ?? 0) . "-" . $filter[1][1][1]) : ($filter[1][0][1] . " or more")) . ")");
                            break;
                        case 2404720969: // ID
                            if(!(
                                $this->guildId >= ($filter[1][0][1] ?? 0) &&
                                $this->guildId <= $filter[1][1][1]
                            )) {
                                $filterPassed = false;
                            }
                            break;
                    }
                }

                if($filterPassed) {
                    $treatment = "None";
                    foreach ($population[0] as $bucket) {
                        foreach ($experiment['buckets'] as $treatmentsList) {
                            if($treatmentsList['id'] == $bucket[0]) {
                                $treatment = $treatmentsList['name'] . ": " . $treatmentsList['description'];
                                break;
                            }
                        }
                        foreach ($bucket[1] as $rollout) {
                            if(
                                $murmurhash >= $rollout['s'] &&
                                $murmurhash <= $rollout['e']
                            ) {
                                if(!str_starts_with($treatment, "None:")) {
                                    array_push($treatments, $treatment);
                                }
                            }
                        }
                    }
                }
            }

            $isOverride = false;
            foreach ($experiment['rollout'][4] as $overrides) {
                $treatment = "None";
                foreach ($experiment['buckets'] as $treatmentsList) {
                    if($treatmentsList['id'] == $overrides['b']) {
                        $treatment = $treatmentsList['name'] . ": " . $treatmentsList['description'];
                        break;
                    }
                }
                foreach ($overrides['k'] as $guildId) {
                    if($guildId == $this->guildId) {
                        if(!str_starts_with($treatment, "None:")) {
                            if(!in_array($treatment, $treatments)) {
                                array_push($treatments, $treatment);
                            }
                            $isOverride = true;
                        }
                    }
                }
            }

            if(!empty($treatments)) {
                array_push($this->experiments, [
                    'title' => $experiment['name'],
                    'treatments' => $treatments,
                    'override' => $isOverride,
                    'filters' => $filters,
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.guild-experiments-modal');
    }
}