<?php

namespace Tests\Feature;


it('stores a location correctly', function () {
    $response = $this->postJson('/api/v1/admin/locations', [
        "name" => "Tesistán",
        "address" => "45200 Tesistán, Jal., México",
        "place_id"=> "ChIJWz3KifemKIQRtDfNWFAlOpI",
        "position"=> [
            "lat"=> 20.8016245,
            "lng"=> -103.47919460000001
        ],
        "tags"=> ['test tag'],
        "fields"=> [
        [
            "id"=> 1,
            "name"=> "campo 1",
            "windows"=> [
            "mon"=> [],
                "tue"=> [],
                "wed"=> [],
                "thu"=> [],
                "fri"=> [
                    [
                        "start"=> "09:00",
                        "end"=> "17:00"
                    ]
                ],
                "sat"=> [
                    [
                        "start"=> "09:00",
                        "end"=> "17:00"
                    ]
                ],
                "sun"=> [
                    [
                        "start"=> "09:00",
                        "end"=> "17:00"
                    ]
                ],
                "all"=> []
            ]
        ]
    ],
        "fields_count"=> 1,
        "steps"=> [
        "location"=> [
            "completed"=> true
        ],
        "fields"=> [
            "completed"=> true
        ]
    ]
    ]);
    $response->assertCreated();
});
