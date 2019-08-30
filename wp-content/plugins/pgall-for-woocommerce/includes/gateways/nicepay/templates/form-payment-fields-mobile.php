<script>
    (function($){
        $('.msspg_cust_type').on('change', function(){
            $('th.cust-type').html( $(this).val() );
        });
    })(jQuery);
</script>

<input type="hidden" name="msspg" value="1">
<table class="msspg-input-fields">
    <tr>
        <th>카드종류</th>
        <td>
            <div>
                <input type="radio" class='msspg_cust_type' name="msspg_cust_type" value="법정 생년월일 (주민번호 앞 6자리)" checked>개인
                <input type="radio" class='msspg_cust_type' name="msspg_cust_type" value="사업자번호">법인
            </div>
        </td>
    </tr>
    <tr>
        <th>카드번호</th>
        <td>
            <div class="pay_id">
                <input type="text" pattern="[0-9]*" inputmode="numeric" maxlength="4" size="4" name="msspg_id_1" value="">
                <span>-</span>
                <input type="text" pattern="[0-9]*" inputmode="numeric" maxlength="4" size="4" name="msspg_id_2" value="">
                <span>-</span>
                <input type="password" pattern="[0-9]*" inputmode="numeric" maxlength="4" size="4" name="msspg_id_3" value="">
                <span>-</span>
                <input type="password" pattern="[0-9]*" inputmode="numeric" maxlength="4" size="4" name="msspg_id_4" value="">
            </div>
        </td>
    </tr>
    <tr>
        <th>유효기간</th>
        <td>
            <div class="expiry">
                <select name="msspg_expiry_month">
                    <?php
                    for( $i = 1 ; $i <=12 ; $i++ ){
                        echo sprintf( '<option value="%02d">%02d</option>', $i, $i );
                    }
                    ?>
                </select>
                <span>/</span>
                <select name="msspg_expiry_year">
                    <?php
                    for( $i = 0; $i <=10 ; $i++ ){
                        echo sprintf( '<option value="%04d">%04d</option>', intval( date('Y') ) + $i,  intval( date('Y') ) + $i );
                    }
                    ?>
                </select>
            </div>
        </td>
    </tr>
    <tr>
        <th class="cust-type">법정 생년월일 (주민번호 앞 6자리)</th>
        <td>
            <div class="cert_no">
                <input type="text" pattern="[0-9]*" inputmode="numeric" maxlength="10" size="10" name="msspg_cert_no" value="">
            </div>
        </td>
    </tr>
    <tr>
        <th>카드 비밀번호 (앞 2자리)</th>
        <td>
            <div class="cert_no">
                <input type="password" pattern="[0-9]*" inputmode="numeric" maxlength="2" size="2" name="msspg_card_pw" value="">
            </div>
        </td>
    </tr>
</table>
