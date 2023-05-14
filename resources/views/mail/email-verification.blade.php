
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
    </head>

    <body class="font-nunito text-base text-black dark:text-white dark:bg-slate-900">

        <!-- Hero Start -->
        <div style="margin-top: 50px;">
            <table cellpadding="0" cellspacing="0" style="font-family: Nunito, sans-serif; font-size: 15px; font-weight: 400; max-width: 600px; border: none; margin: 0 auto; border-radius: 6px; overflow: hidden; background-color: #fff; box-shadow: 0 0 3px rgba(60, 72, 88, 0.15);">
                <thead>
                    <tr style="background-color: #980f08; padding: 3px 0; border: none; line-height: 68px; text-align: center; color: #fff; font-size: 24px; letter-spacing: 1px;">
                        <th scope="col"><img src="" alt=""></th>
                    </tr>
                </thead>
    
                <tbody>
                    <tr>
                        <td style="padding: 48px 24px 0; color: #161c2d; font-size: 18px; font-weight: 600;">
                            Hello, {{ $name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 15px 24px 15px; color: #8492a6;">
                            Thanks for creating an account with EasyBuy4Me. <br>Your Verification Token is: <strong>{{ $veriToken }}</strong><br>Kindly reply with this token on the bot or click on the button below to complete your registration :
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding: 15px 24px;">
                            <a href="{{ $url }}" style="padding: 8px 20px; outline: none; text-decoration: none; font-size: 16px; letter-spacing: 0.5px; transition: all 0.3s; font-weight: 600; border-radius: 6px; background-color: #980f08; color: #ffffff;">Confirm Email Address</a>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding: 15px 24px 0; color: #8492a6;">
                            This link will be active for 10 min from the time this email was sent.
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding: 15px 24px 15px; color: #8492a6;">
                            EasyBuy4Me <br> Support Team
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding: 16px 8px; color: #8492a6; background-color: #f8f9fc; text-align: center;">
                            © <script>document.write(new Date().getFullYear())</script> Easybuy4Me.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Hero End -->
    </body>
</html>
