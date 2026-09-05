<?php
session_start();

include("connection.php");
include("inc/func.php");

// Initialize form values to avoid undefined variable notices on first load.
$prename = '';
$lastname = '';
$email = '';
$phone = '';
$street = '';
$zip = '';
$city = '';
$notes = '';

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
