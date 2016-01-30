API
===

/shows

    {
        "id": integer,
        "name": string,
        "fullName": string,
        "vanue": string,
        "performances": {
            "id": integer,
            "startsAt": string,
            "seatMap": integer,
            "prices": {
                "<rate-id>": {
                    "<band-id>": price,
                },
            }
        },
        "rates": {
            "id": <name>-string,
        },
        "defaultRate_id": "id",
    }

/seatmaps

    {
        "id": integer,
        "blocks": {
            "id": {
                "name": string,
                "offset": [integer, integer],
                "rotation": integer,
                "rows": {
                    "id": {
                        "name": string,
                        "seats": {
                            "id": {
                                "number": integer,
                                "restricted": bool,
                                "bandId": integer,
                            }
                        }
                    }
                }
            }
        },
        "bands": {
            "id": <name>string,
        },
    }

/availability/{performance}

    [<taken-seat-id>, ]

/seatset
    {
        "dateCreated": timestamp,
        "annulled": boolean,
        "ephemeral": boolean,
        "seats":
        [
          {
              "id": integer,
              "block": integer,
              "row": integer,
              "seatNumber": integer,
              "rate": integer,
              "price": integer,
          }
        ]
    }

/completion
    {
        "email": string,
        "token": string,
        "pymtAddrLine1": string,
        "pymtAddrZip": string,
        "pymtAddrState": string,
        "pymtAddrCity": string,
        "pymtAddrCountry": string,
    }
