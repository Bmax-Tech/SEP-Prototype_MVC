<div style="background: rgb(113, 125, 97);color: #FFF;font-size: 15px;padding: 7px 10px;border-bottom: 3px solid #035600;margin-bottom: 10px">
    Admin Registration
</div>
<div class="container c_container" style="background-color:#E8E8E8">
    <div class="col-lg-6" style="padding-top:50px;padding-left: 50px">
       <div>


            <ul class="c_ul_1" >

                <li>
                    <span>First Name</span><span class="c_warning_tips_reg" id="wrn_first_name"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span> enter valid first name</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="text" class="c_text_box_1" spellcheck="false" name="first_name" id="first_name" onkeydown='only_alph(event)' onkeypress="remove_wrn('first_name')"  onchange="remove_wrn('first_name')" autocomplete="off"/>
                </li>
                <li>
                    <span>Last Name</span><span class="c_warning_tips_reg" id="wrn_last_name"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span> enter valid last name</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="text" class="c_text_box_1" spellcheck="false" name="last_name" id="last_name" onkeydown='only_alph(event)' onkeypress="remove_wrn('last_name')" onchange="remove_wrn('last_name')" autocomplete="off"/>
                </li>
                <li>
                    <span>Email Address</span><span class="c_warning_tips_reg" id="wrn_email"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span> enter email address</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="text" class="c_text_box_1" spellcheck="false" name="email" id="email"  onkeyup="check_reg_existing('email',this.value)" onkeypress="remove_wrn('email')" onchange="remove_wrn('email')" placeholder="Eg:- eayurveda@gmail.com"/>
                </li>
                <li>
                    <span>Username</span><span class="c_warning_tips_reg " id="wrn_username">enter username</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="text" class="c_text_box_1" spellcheck="false" name="username" id="user_name" onkeyup="check_reg_existing('username',this.value)" onkeypress="remove_wrn('username')" onchange="remove_wrn('username')" autocomplete="off"/>
                </li>
                <li>
                    <span>Password</span><span class="c_warning_tips_reg" id="wrn_password">enter password</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="password" class="c_text_box_1" spellcheck="false" name="password" id="password" onkeypress="remove_wrn('password')" onchange="remove_wrn('password')" autocomplete="off"/>
                </li>
                <li>
                    <span>Confirm Password</span><span class="c_warning_tips_reg" id="wrn_confirm_password">enter valid confirm password</span>
                </li>
                <li class="c_add_margin_20 c_form_margin_10">
                    <input type="password" class="c_text_box_1" spellcheck="false" name="confirm_password" id="confirm_password" onkeypress="remove_wrn('confirm_password')" onchange="remove_wrn('confirm_password')" autocomplete="off"/>
                </li>
                <li style="padding:0px 8px;margin-top:55px">
                    <button type="submit" class="c_button_1" onClick="valid_registration()">Register</button>
                </li>
            </ul>
        </div>

    </div>

</div>

<div id="adminregpoup" class="container pat_confirm1_box" >

    <div class="center-block pat_confirm1_box_wrapper" style="margin-right: 55%;margin-top: 15%;width: 375px">
        <button  class="pat_close_btn" onclick="adminregpoup_close()"><img src="{{ URL::asset('assets/img/close_btn.png') }}"></button>
        <div style="background: #4CBC5B;height: 145px;padding-top: 32px">
            <div class="container c_no_padding col-lg-12">
                <div class="col-lg-10 c_no_padding" style="margin-left: 30px">
                    <ul class="c_ul_1">
                        <li><span style="font-size: 20px;font-weight: 100;margin-left: 30px;color: #FFF">Please Confirm Submit </span></li>

                        <li> <div style="padding-top: 30px">
                                <div class="col-lg-3 ">
                                    <button class="pat_view_btn_1" onclick="addadmin()" >Confirm</button>
                                </div>
                                <div class="col-lg-3" style="margin-left: 100px">
                                    <button class="pat_view_btn_1" onclick="adminregpoup_close()" >Cancel</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>