<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<script>
    jQuery(document).ready(function ($) {
        $('#pafw_write_smart_review').on('click', function () {
            if ($(this).is(':checked')) {
                $('div.pafw-review').css('display', 'block');
            } else {
                $('div.pafw-review').css('display', 'none');
            }
        });

        $('input[name=pafw_smart_review_rate]').on('click', function () {
            $('textarea[name=pafw_smart_review_content]').val($('input[name=pafw_smart_review_rate]:checked').data('content'));
        });

        $('input[name=pafw_smart_review_rate]:checked').trigger('click');
    });
</script>
<style>
    .pafw-review {
        margin-top: 10px;
    }

    .pafw-review-wrapper > h3 {
        margin-bottom: 14px !important;
    }

    .pafw-review-wrapper > label {
        font-weight: 400;
        cursor: pointer;
        padding-left: 0 !important;
    }

    .pafw-review-wrapper>label:before,
    .pafw-review-wrapper>label:after,
    .pafw-review .pafw-smart-reivew-item label:before,
    .pafw-review .pafw-smart-reivew-item label:after {
        display: none !important;
    }

    .pafw-review-wrapper > input {
        background: #2085a2;
        -webkit-appearance: none;
        display: inline-block !important;
        width: 14px;
        height: 14px;
        position: relative !important;
        font-weight: bold;
        border-radius: 3px;
        vertical-align: middle;
    }

    .pafw-review-wrapper>input:focus {
        outline: none;
    }

    .pafw-review-wrapper > input[type=checkbox]:checked:before {
        content: "";
        display: block;
        position: absolute;
        left: 0;
        top: 0;
        color: white;
        width: 9px;
        height: 9px;
        background: url("<?php echo PAFW()->plugin_url() . '/assets/images/rating/face/check.png'; ?>") center center !important;
        background-size: cover !important;
        margin: 2px !important;
    }

    .pafw-review-wrapper input {
        margin: 0 5px 0 0;
    }

    .pafw-review .pafw-smart-reivew-item {
        position: relative !important;
    }

    .pafw-review .pafw-smart-reivew-item input {
        opacity: 0;
        visibility: hidden;
        position: absolute;
        left: 0;
    }

    .pafw-review .pafw-smart-reivew-item label {
        display: inline-block !important;
        border-radius: 6px !important;
        padding: 5px 5px 5px 31px !important;
        margin-bottom: 6px;
        background-size: 21px !important;
        background-repeat: no-repeat !important;
        background-position: 5px center !important;
        cursor: pointer;
        transition: all .2s ease-in-out;
        line-height: 1.6em !important;
    }

    div.pafw-smart-reivew-item input:checked + label {
        background-color: #ececec !important;
    }
</style>
<div class="pafw-review-wrapper">
    <h3><?php _e( '구매평 작성하기', 'pgall-for-woocommerce' ); ?></h3>
    <input type="checkbox" id="pafw_write_smart_review" name="pafw_write_smart_review" checked><label
            for="pafw_write_smart_review"><?php _e( '구매평 자동 등록에 동의합니다.', 'pgall-for-woocommerce' ); ?></label>
    <div class="pafw-review">
		<?php foreach ( $rate_options as $rate_option ) : ?>
            <div class="pafw-smart-reivew-item">
                <input type="radio" id="pafw_smart_review_rate<?php echo $rate_option['rate']; ?>" name="pafw_smart_review_rate" data-content="<?php echo $rate_option['content']; ?>" value="<?php echo $rate_option['rate']; ?>" <?php echo 'yes' == $rate_option['default'] ? 'checked' : ''; ?>>
                <label style="background-image: url('<?php echo PAFW()->plugin_url() . '/assets/images/rating/face/' . $rate_option['rate'] . '.png'; ?>')" for="pafw_smart_review_rate<?php echo $rate_option['rate']; ?>">
                    <span style="padding: 0 6px;"><?php echo $rate_option['label']; ?></span>
                </label>
            </div>
		<?php endforeach; ?>
        <div style="<?php echo 'no' == get_option( 'pafw-user-can-edit-comment', 'no' ) ? 'display:none' : ''; ?>">
            <textarea name="pafw_smart_review_content" placeholder="<?php echo get_option( "pafw-smart-review-placeholder", __( '리뷰를 작성 해 주세요.', 'pgall-for-woocommerce' ) ); ?>"></textarea>
        </div>
    </div>
</div>