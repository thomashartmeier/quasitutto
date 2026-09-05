<?php
session_start();

include("connection.php");
include("inc/func.php");

// Initialize form values to avoid undefined variable notices on first load.
$prename = '';
$lastname = '';
$email = '';
$city = '';
$feedback = '';

$clientIp = getClientIpAddress();
$allowForm = isSwissVisitor($clientIp);
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
            $submitted = $_POST['submitted'] ?? '';

            if (!empty($submitted) && $allowForm)
            {
                // the form was submitted, so we check for valid form data first
                $prename  = filter_var(trim($_POST['prename']),  FILTER_SANITIZE_STRING);
                $lastname = filter_var(trim($_POST['lastname']), FILTER_SANITIZE_STRING);
                $email    = filter_var(trim($_POST['email']),    FILTER_SANITIZE_EMAIL);
                $city     = filter_var(trim($_POST['city']),     FILTER_SANITIZE_STRING);
                $feedback = filter_var(trim($_POST['feedback']), FILTER_SANITIZE_STRING);
                $math     = filter_var(trim($_POST['math']),     FILTER_SANITIZE_STRING);

                // check the math test
                if ($math != 11)
                {
                    // issue modal to point to the wrong answer
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
                        $sql = "INSERT INTO clients (createDate,    prename,    lastname,     email,    phone,  mobile,    address,    createdByUserId,  wantsNewsletters, notes) VALUES
                                                    ('$createDate', '$prename', '$lastname', '$email', '', '', '$city', $createdByUserId, 1,                '')";

                        $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");

                        // get ID of the newly created client
                        $clientId = mysqli_insert_id($conn);
                    }

                    // insert new guestbook entry
                    $sql = "INSERT INTO guestbookentries (createDate, createdByUserId, clientId, feedback, reviewedByUserId, active, frontpage, notes) VALUES
                                               ('$createDate', $createdByUserId, $clientId, '$feedback', NULL, 0, 0, '')";

                    $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");

                    // issue modal to thank the client for the guestbook entry
                    echo "<div class=\"modal\" tabindex=\"-1\" role=\"dialog\" id=\"guestbookSuccessModal\">\n";
                    echo "  <div class=\"modal-dialog\" role=\"document\">\n";
                    echo "    <div class=\"modal-content bg-qtgreen comic\">\n";
                    echo "      <div class=\"modal-header\">\n";
                    echo "        <h5 class=\"modal-title\">Eintrag im Gästebuch aufgegeben</h5>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-body\">\n";
                    echo "        <p>Vielen Dank für Ihren Eintrag im Gästebuch. Nach einer Überprüfung wird er auf der Webseite veröffentlicht.</p>\n";
                    echo "      </div>\n";
                    echo "      <div class=\"modal-footer\">\n";
                    echo "        <button type=\"button\" class=\"btn btn-primary\" data-bs-dismiss=\"modal\">OK</button>\n";
                    echo "      </div>\n";
                    echo "    </div>\n";
                    echo "  </div>\n";
                    echo "</div>\n";
                    echo "<script>\n";
                    echo "document.addEventListener(\"DOMContentLoaded\", function() {\n";
                    echo "  var el = document.getElementById(\"guestbookSuccessModal\");\n";
                    echo "  if (el) new bootstrap.Modal(el).show();\n";
                    echo "});\n";
                    echo "</script>";

                    // send out email to the QT people to let them know a new guestbook entry has been submitted
                    $to = "kontakt@quasitutto.ch";

                    $subject = "Neuer Gästebucheintrag bei Quasitutto";

                    $message = "Liebe Quasituttis,\n\nsoeben ist ein neuer Gästebucheintrag eingegangen. Bitte loggt euch auf https://admin.quasitutto.ch ein, um ihn zu überprüfen und gegebenenfalls zu veröffentlichen.";

                    $headers = "From: kontakt@quasitutto.ch";

                    mail($to, $subject, $message, $headers);

                    // reset form fields after submission
                    $prename = '';
                    $lastname = '';
                    $email = '';
                    $city = '';
                    $feedback = '';
                }
            }
            else if (!empty($submitted) && !$allowForm)
            {
                echo "<div class=\"alert alert-warning mx-md-5 mt-3\" role=\"alert\">\n";
                echo "  Kann nicht angezeigt werden.\n";
                echo "</div>\n";
            }
            else
            {
                // nothing to say if form was not submitted yet
            }
            ?>

            <div class="container px-0 py-5">
                <div class="h1 fw-bolder">Gästebuch</div>
            </div>

            <section class="py-4 bg-qtgreen comic corner-gif mx-md-5" id="aktuell">
                <img src="img/left.png" class="corner-img-qtyellow left" alt="" />
                <img src="img/left.png" class="corner-img-qtred right mirror-vertical" alt="" />

                <div class="container px-1 px-md-5">
                    <div class="row gx-5">
                        <div class="col-xl-6 mb-1">
                            <p class="lead fw-normal px-4 pt-4"><span style="background-color: white;">Wir freuen uns riesig über Feedback und neue Einträge in unserem Gästebuch! <i class="bi bi-balloon-heart"></i></span></p>
                            <div class="comic bg-white text-center pb-4 pt-4"><img src="img/mb.png" alt="Quasitutto Logo f" /></div>
                        </div>
                        <div class="col-xl-6 mb-1">
                            <div class="comic bg-white p-4">
                                <?php if ($allowForm) { ?>
                            <form id="formIdentifier" method="POST" action="./gastebuch.php">
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
                                            <input name="lastname" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $lastname; ?>" required> (*)
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>E-mail:</td>
                                        <td>
                                            <input name="email" type="email" style="width: 70%; box-sizing: border-box;" value="<?php echo $email; ?>" required> (*)
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Ort:</td>
                                        <td>
                                            <input name="city" type="text" style="width: 70%; box-sizing: border-box;" value="<?php echo $city; ?>" required>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>Ihr Feedback:</td>
                                        <td>
                                            <textarea name="feedback" rows="4" style="width: 100%; box-sizing: border-box;" required><?php echo $feedback; ?></textarea>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td>4 + 7 =</td>
                                        <td>
                                            <input name="math" type="number" style="width: 30%; box-sizing: border-box;" required> (no robot check)
                                        </td>
                                    </tr>
                                </table>
                                (*) Nur für uns intern, wird im veröffentlichten Gästebucheintrag nicht angezeigt.
                                <hr>
                                <input type='hidden' value='1' name='submitted'>
                                <p><input type="submit" value="Absenden"></p>
                            </form>
                            <?php } else { ?>
                            <div class="comic bg-white p-4">
                                <p class="mb-0 lead fw-normal">
                                    Kann nicht angezeigt werden.
                                </p>
                            </div>
                            <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <!-- Footer -->
        <?php include "./inc/footer.html" ?>

    </body>
</html>
