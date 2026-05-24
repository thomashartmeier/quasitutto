<?php
session_start();

include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">

    <!-- Header -->
    <?php include "./inc/head.html" ?>

    <body class="d-flex flex-column h-100">
        <main class="flex-shrink-0">

            <!-- Navigation -->
            <?php include "./inc/nav.html" ?>

            <!-- Form handling -->
            <?php
            $submitted = $_POST['submitted'];

            if (!empty($submitted))
            {
                // the form was submitted, so we check for valid form data first
                $prename = $_POST['prename'];
                $lastname = $_POST['lastname'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $street = $_POST['street'];
                $zip = $_POST['zip'];
                $city = $_POST['city'];
                $address = $_POST['street'] . ", " . $_POST['zip'] . " " . $_POST['city'];
                $notes = $_POST['notes'];
                $math = $_POST['math'];

                // check the math test
                if ($math != 11)
                {
                    // issue modal to thank the client for the order
                    echo "<div class=\"modal\" tabindex=\"-1\" role=\"dialog\" id=\"wrongMath\">\n";
                    echo "  <div class=\"modal-dialog\" role=\"document\">\n";
                    echo "    <div class=\"modal-content bg-qtred comic\">\n";
                    echo "      <div class=\"modal-header\">\n";
                    echo "        <h5 class=\"modal-title\">Rechenfehler</h5>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-body\">\n";
                    echo "        <p>Bitte 4 + 7 korrekt lösen.</p>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-footer\">\n";
                    echo "        <button type=\"button\" class=\"btn btn-primary\" data-bs-dismiss=\"modal\">OK</button>\n";
                    echo "      </div>\n";
                    echo "    </div>\n";
                    echo "  </div>\n";
                    echo "</div>\n";
                    echo "<script>\n";
                    echo "document.addEventListener(\"DOMContentLoaded\", function() {\n";
                    echo "  var el = document.getElementById(\"wrongMath\");\n";
                    echo "  if (el) new bootstrap.Modal(el).show();\n";
                    echo "});\n";
                    echo "</script>";
                }
                else
                {
                    // all good, we have valid form data and can create a new database entry

                    // create today's date
                    $createDate = date("Y-m-d");

                    // always created by the www-user with ID 1
                    $createdByUserId = 1;

                    // check if we already have a client with the same email address
                    $sql = "SELECT * FROM clients WHERE email='$email'";

                    $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");

                    // if we have a client with this email address, use it instead of creating a new one
                    $clientId = null;

                    if (mysqli_num_rows($query) > 0)
                    {
                        $client = mysqli_fetch_assoc($query);

                        // get ID of the client (and do not update any fields)
                        // TODO: we could check if we have some updated data from the client...
                        $clientId = $client['id'];
                    }
                    // else we create a new client
                    else
                    {
                        $home = '';
                        $mobile = '';

                        // if phone starts with 07(6,7,8,9) we assume it's a mobile number and save it in the mobile column, otherwise we save it in the phone column
                        if ($phone != "" && preg_match("/^07[6-9][0-9]{7}$/", $phone))
                        {
                            $mobile = $phone;
                        }
                        else
                        {
                            $home = $phone;
                        }

                        $sql = "INSERT INTO clients (createDate,    prename,    lastname,     email,    phone,  mobile,    address,    createdByUserId,  wantsNewsletters, notes) VALUES
                                                    ('$createDate', '$prename', '$lastname', '$email', '$home', '$mobile', '$address', $createdByUserId, 1,                '')";

                        $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");

                        // get ID of the newly created client
                        $clientId = mysqli_insert_id($conn);
                    }

                    // create order number
                    $orderNumber1 = rand(0, 999);
                    $orderNumber2 = rand(0, 999);

                    // compose string of order number
                    $orderNumberString = sprintf("%03d-%03d", $orderNumber1, $orderNumber2);

                    // insert new order entry
                    $sql = "INSERT INTO orders (orderNumber,          createDate,    createdByUserId,  clientId,  orderStatusId, paymentStatusId, price, notes) VALUES
                                               ('$orderNumberString', '$createDate', $createdByUserId, $clientId, 0,             0,               0.00,  '$notes')";

                    $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");

                    // issue modal to thank the client for the order
                    echo "<div class=\"modal\" tabindex=\"-1\" role=\"dialog\" id=\"orderSuccessModal\">\n";
                    echo "  <div class=\"modal-dialog\" role=\"document\">\n";
                    echo "    <div class=\"modal-content bg-qtgreen comic\">\n";
                    echo "      <div class=\"modal-header\">\n";
                    echo "        <h5 class=\"modal-title\">Auftrag aufgegeben</h5>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-body\">\n";
                    echo "        <p>Vielen Dank für den Auftrag. Wir werden Sie in Kürze kontaktieren.</p>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-footer\">\n";
                    echo "        <button type=\"button\" class=\"btn btn-primary\" data-bs-dismiss=\"modal\">OK</button>\n";
                    echo "      </div>\n";
                    echo "    </div>\n";
                    echo "  </div>\n";
                    echo "</div>\n";
                    echo "<script>\n";
                    echo "document.addEventListener(\"DOMContentLoaded\", function() {\n";
                    echo "  var el = document.getElementById(\"orderSuccessModal\");\n";
                    echo "  if (el) new bootstrap.Modal(el).show();\n";
                    echo "});\n";
                    echo "</script>";

                    // send out email to the client and cc to quasitutto
                    $to = $email;

                    $subject = "Bestätigung Ihres Auftrags bei Quasitutto (Auftragsnummer: $orderNumberString)";

                    $message = "Liebe/r $prename $lastname,\n\nvielen Dank für Ihren Auftrag bei Quasitutto. ";
                    $message .= "Wir haben Ihre Anfrage erhalten und werden uns in Kürze mit Ihnen in Verbindung setzen, ";
                    $message .= "um die Details zu besprechen.\n\n";
                    $message .= "Auftragsnummer: $orderNumberString\n";
                    $message .= "Kunde: $prename $lastname\n";
                    $message .= "E-Mail: $email\n";
                    $message .= "Auftrag: $notes\n\n";
                    $message .= "Mit reparaturfreudigen Grüßen\nIhr Quasitutto Team";

                    $headers = "From: kontakt@quasitutto.ch";
                    $headers .= "\r\nCc: kontakt@quasitutto.ch";

                    mail($to, $subject, $message, $headers);
                }
            }
            else
            {
                // nothing to say if form was not submitted yet
            }
            ?>

            <!-- Header-->
            <header class="bg-white py-3">
                <div class="container px-0">
                    <div class="row justify-content-center">
                        <div class="col text-center mb-2">
                            <img src="img/logo_gross.png" class="img-fluid" alt="..." />
                        </div>
                    </div>
                    <div class="row align-items-center justify-content-center">
                        <div class="text-center">
                            <p class="lead fw-normal mb-2 text-muted">Der andere Dienstleister, der ganz und gar und unkompliziert auf die Bedürfnisse und Wünsche seiner Kundschaft eingeht.</p>
                            <p class="lead fw-normal mb-2 text-muted">Unser Name ist Programm &mdash; wir machen QUASI ALLES.</p>
                        </div>
                        <!-- <div class="col-lg-8 col-xl-7 col-xxl-6">
                            <div class="my-1 text-center text-xl-start">
                                <div class="banner bg-qtyellow rounded-3 text-center">
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <p class="lead fw-normal text-muted mb-4">Unsere Werkstatt macht Sommerpause und bleibt während den Ferien geschlossen.
Ab <strong>Mittwoch, 20. August 2025</strong> sind wir gerne jeweils am Mittwoch von 14 bis 18 Uhr wieder für Sie da.
Für Dienstleistungen bei Ihnen Zuhause sind wir auch während den Ferien erreichbar unter 077 403 03 06 oder <a href="mailto:kontakt@quasitutto.ch">kontakt@quasitutto.ch</a>
Auch die Buchhandlung <strong>el LIESYUM</strong> an der Schwandelstrasse geht vom 21. Juli bis zum 3. August in die Sommerferien.<br/>
Wir wünschen Ihnen erfrischende Sommertage, Ihr Quasitutto Team</p>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                    <i class="bi bi-sun-fill"></i>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </header>

            <!-- Wer sind wir? -->
            <section class="py-4 bg-qtgreen comic corner-gif mx-md-5" id="kontakt">
                <img src="img/left.png" class="corner-img-qtyellow left" alt="" />
                <img src="img/left.png" class="corner-img-qtred right mirror-vertical" alt="" />

                <div class="container px-1 px-md-5">
                    <div class="row gx-0 gx-md-5">
                        <div class="col-xl-6 mb-1">
                            <h2 class="fw-bolder mb-4">Wer sind wir?</h2>
                            <div class="comic bg-white">
                                <img style="padding: 0.3em; margin-top:1em" class="card-img-top" src="img/qtTeam2023.jpg" alt="Quasitutto Team 2023" />
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3">Quasitutto Team</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="comic bg-white p-4">
                                <p class="mb-0 lead fw-normal">Wir sind ein kleiner Dienstleistungsbetrieb von vorwiegend pensionierten Frauen und Männern mit verschiedenem beruflichem Hintergrund. Daher auch unser breites Angebot. Es ist uns ein Anliegen, Gutes für unsere Umwelt zu tun. Motto: Besser reparieren statt wegwerfen!</p>
                            </div>
                            <p class="lead fw-normal px-4 pt-4"><span style="background-color: white;">Mehr zum Verein und Kontakt <a href="verein_kontakt.php">hier</a>.</span></p>
                            <p class="lead fw-normal px-4"><span style="background-color: white;">Überblick über unser Angebot <a href="angebot.php">hier</a>.</span></p>
                            <p class="lead fw-normal px-4"><span style="background-color: white;">Auftragsbeispiele <a href="beispielprojekte.php">hier</a>.</span></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Auftragsformular -->
            <section class="py-4 bg-qtyellow comic corner-gif mx-md-5" id="auftrag">
                <img src="img/left.png" class="corner-img-qtred left" alt="" />
                <img src="img/left.png" class="corner-img-qtgreen right mirror-vertical" alt="" />

                <div class="container px-1 px-md-5">
                    <div class="row gx-0 gx-md-5">
                        <div class="col-xl-6 mb-1">
                            <h2 class="fw-bolder mb-4">Auftragsformular</h2>
                            <form id="formIdentifier" method="POST" action="./index.php">
                                <table>
                                    <tr valign="top">
                                        <td>Vorname:</td>
                                        <td>
                                            <input name="prename" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $prename; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Nachname:</td>
                                        <td>
                                            <input name="lastname" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $lastname; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>E-mail:</td>
                                        <td>
                                            <input name="email" type="email" style="width: 70%; box-sizing: border-box;" value="<?php echo $email; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Telefon/Mobile:</td>
                                        <td>
                                            <input name="phone" type="tel" pattern="[0-9]{3}( )?[0-9]{3}( )?[0-9]{2}( )?[0-9]{2}" placeholder="z.B. 079 123 45 67" style="width: 70%; box-sizing: border-box;" value="<?php echo $phone; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Strasse/Nr.:</td>
                                        <td>
                                            <input name="street" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $street; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>PLZ:</td>
                                        <td>
                                            <input name="zip" type="number" pattern="[0-9]{4}" placeholder="z.B. 8800" style="width: 70%; box-sizing: border-box;" value="<?php echo $zip; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Ort:</td>
                                        <td>
                                            <input name="city" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $city; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Ihr Anliegen:</td>
                                        <td>
                                            <textarea name="notes" rows="4" style="width: 100%; box-sizing: border-box;" required><?php echo $notes; ?></textarea>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>4 + 7 =</td>
                                        <td>
                                            <input name="math" type="number" style="width: 30%; box-sizing: border-box;" required> (no robot check)
                                        </td>
                                    </tr>
                                </table>
                                <hr>
                                <input type='hidden' value='1' name='submitted'>
                                <p><input type="submit" value="Absenden"></p>
                            </form>
                        </div>
                        <div class="col-xl-6 mb-1">
                            <p class="lead fw-normal px-4"><span style="background-color: white;">Verwenden Sie dieses Formular, um uns einen Auftrag zu übermitteln. Vielen Dank! <i class="bi bi-balloon-heart"></i></span></p>
                            <div class="comic bg-white text-center pb-4 pt-4"><img src="img/mb.png" alt="Quasitutto Logo f" /></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Wo sind wir? -->
            <section class="py-4 bg-qtred comic corner-gif mx-md-5" id="aktuell">
                <img src="img/left.png" class="corner-img-qtgreen left" alt="" />
                <img src="img/left.png" class="corner-img-qtyellow right mirror-vertical" alt="" />

                <div class="container px-1 px-md-5">
                    <div class="row gx-0 gx-md-5">
                        <div class="col-xl-6 mb-1">
                            <h2 class="fw-bolder mb-4">Wo sind wir?</h2>
                            <div class="comic bg-white p-4 lead fw-normal">Unsere <b>Werkstatt</b> befindet sich an der Dorfstrasse 65 beim Schützenhaus Thalwil und ist jeden Mittwoch von 14 bis 18 Uhr geöffnet. Wir sind per ÖV bequem erreichbar (Bus/Postautohaltestelle Schützenhaus, Linie 140 und 240).</div>
                            <div class="comic bg-white p-4 lead fw-normal">
                                Sie finden uns auch in der <b>Buchhandlung el LIESYUM</b> an der Schwandelstrasse 2, mitten in Thalwil. Kleinere tragbare Gegenstände können dort abgegeben und (hoffentlich geflickt) wieder abgeholt werden.
                                <ul class="mt-3">
                                    <li><b>Dienstag bis Freitag:</b> 9 bis 12 Uhr, 13:30 bis 18 Uhr</li>
                                    <li><b>Samstag:</b> 10 bis 15 Uhr</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="comic bg-white p-4 lead fw-normal text-center">
                                <iframe width="350" height="350" src="https://www.openstreetmap.org/export/embed.html?bbox=8.56128662824631,47.286871108534775,8.563700616359712,47.28838120360025&amp;layer=mapnik&amp;mlat=47.287626&amp;mlon=8.562494" style="border: 0"></iframe>
                                <br/><a href="https://www.openstreetmap.org/?mlat=47.28756&amp;mlon=8.56248#map=16/47.29106/8.56375" target="_blank">Auf grosse Karte wechseln</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            <!-- Referenzen -->
            <div class="py-4 bg-light">
                <div class="container px-5">
                    <div class="row gx-5 justify-content-center">
                        <div class="col-lg-5 mb-5 mb-lg-0">
                            <div class="text-center">
                                <div class="mb-2 fst-italic">"Das Umrüsten von Halogenlampen auf LED ist ein abenteuerliches Unterfangen. Es hat wunderbar geklappt ! Quasitutto iat ein tolles Projekt. Herzlichen Dank für Eure wunderbare Initiative!"</div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <img class="rounded-circle me-3" src="img/f.png" alt="..." />
                                    <div class="fw-bold">
                                        Silvia Staub, 23. November 2024
                                        <span class="fw-bold text-primary mx-1">/</span>
                                        Thalwil
                                    </div>
                                </div>
                            </div>
                            <p class="mb-5"></p>
                            <div class="text-center">
                                <div class="mb-2 fst-italic">"Cool gibt es Euch - Reparieren statt entsorgen ist heutzutage ein absolutes Muss."</div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <img class="rounded-circle me-3" src="img/m.png" alt="..." />
                                    <div class="fw-bold">
                                        Berni, 4. Oktober 2023
                                        <span class="fw-bold text-primary mx-1">/</span>
                                        Thalwil
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="text-center">
                                <div class="mb-2 fst-italic">"Wir haben Quasitutto auf dem Willkommensanlass Thalwil kennenlernen dürfen. Als unsere neu installierte LED Lampe durch einen veralteten Dimmer nicht einwandfrei funktionierte, haben wir gleich an Quasitutto gedacht und haben eine Anfrage gestellt. Innerhalb kürzester Zeit haben wir einen Anruf von Herrn Liniger erhalten, der uns das Problem erklärt hat verschiedene Lösungswege aufgezeigt hat. Einige Tage später kam Herr Liniger und konnte unkompliziert unseren Dimmer umrüsten, sodass wir nun unsere Lampe problemlos nutzen können. Herzlichen Dank für Ihre Hilfe!"</div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <img class="rounded-circle me-3" src="img/m.png" alt="..." />
                                    <div class="fw-bold">
                                        Dominik A., 31. Oktober 2024
                                        <span class="fw-bold text-primary mx-1">/</span>
                                        Thalwil
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <?php include "./inc/footer.html" ?>

    </body>
</html>
