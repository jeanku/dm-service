<?php

/**
 * 异常Code
 * @desc 异常码有8位 前两位是系统code,中间两位是业务code,最后四位是异常code
 * @desc 99开头的是公用的异常code
 * Manage code : {11000000 ~ 11999999}
 * 公用异常code {99000000 ~ 99999999}
 * @return array
 */

return [

    /*****************************公用异常code {990000 ~ 999999}*********************************/
    'REQUEST_ERROR'  => ['接口请求失败','999999'],
    'REQUEST_PARAMETERS_ERROR'  => ['请求参数异常','999998'],
    'REQUEST_TOO_FREQUENT_EXCEPTION'  => ['请求过于频繁','999997'],



    /*****************************考驾照异常code {100000 ~ 100099}*********************************/
    'DATA_ALREADY_DELETE_EXCEPTION'     => ['当前数据已经失效', '100002'],
    'DATA_SIGN_CHANGE_EXCEPTION'        => ['当前数据异常', '100003'],
    'DATA_NOT_FIND_EXCEPTION'           => ['未找到对应的数据信息', '100009'],
    'SHORT_URL_ADD_EXCEPTION'           => ['短链接创建失败','100010'],
    'SHORT_URL_KEY_NOT_VALID_EXCEPTION' => ['未知的短链信息','100011'],
    'SHORT_URL_FROZEN_EXCEPTION'        => ['短链已冻结','100012'],
    'SHORT_URL_EXPIRED_EXCEPTION'       => ['短链已过期','100013'],


    /*****************************教练异常code {100100 ~ 100200}*********************************/
    'COACH_NOT_EXIST_EXCEPTION'         => ['教练不存在', '100011'],
    'COACH_FROZEN_EXCEPTION'            => ['当前教练已冻结', '100012'],


    /*****************************积分异常code {100200 ~ 100300}*********************************/
    'COACH_INVITE_ADDRESS_EXCEPTION'    => ['教练招生地址积分只能添加一次', '100200'],
    'COACH_POINT_ACCOUNT_NOT_EXIST_EXCEPTION' => ['当前教练还没有积分', '100201'],
    'POINT_ACCOUNT_NOT_FIND_EXCEPTION'  => ['积分账户不存在', '100202'],
    'POINT_NOT_ENOUGH_EXCEPTION'        => ['当前积分不足', '100203'],
    'COACH_POINT_ADDED_EXCEPTION'       => ['当前教练积分只能添加一次', '100204'],
    'COACH_ADDRESS_NOT_FIND_EXCEPTION'  => ['当前教练没有招生地址', '100205'],
    'ADD_POINT_TOO_TIMES_EXCEPTION'     => ['添加积分次数已超上限', '100206'],
    'UNDEFINED_POINT_TYPE_EXCEPTION'    => ['未知的积分类型', '100207'],
    'UNDEFINED_DECODE_ID_EXCEPTION'     => ['未知扣减积分id', '100208'],


    /*****************************学员评论异常code {100300 ~ 100400}*********************************/
    'NOT_ATTNED_USER_CANNOT_COMMENT'    => ['未关注用户不能评论', '100300'],
    'RELEASE_ATTNED_USER_CANNOT_COMMENT'=> ['解除绑定的用户不能评论', '100301'],
    'COACH_CANNOT_COMMENT_SELF_EXCEPTION'    => ['教练不能评论自己', '100302'],
    'NOT_FIND_COMMENT_EXCEPTION'        => ['当前评论不存在或者已删除不存在', '100303'],
    'NOT_FIND_USER_EXCEPTION'           => ['未找到对应的用户信息', '100304'],
    'PERMISSION_DENY_FOR_COMMENT'       => ['你不权操作该评论', '100305'],
    'ALREADY_REPLYED_COMMENT_EXCEPTION' => ['你已经回复过该评论', '100306'],
    'USER_COMMENT_MAX_EXCEPTION'        => ['你最多只能评论5次','100307'],


    /*****************************文章异常code {100400 ~ 100500}*********************************/
    'ARTICLE_NOT_EXIST_EXCEPTION'       => ['收藏的文章不存在', '100400'],


    /*****************************学员咨询异常code {100500 ~ 100600}*********************************/
    'STUDENT_CONSULT_CLASSID_NOT_FOUND' => ['班级不存在', '100500'],
    'STUDENT_CONSULT_CLASSID_ATTENDED'  => ['您已经报过名', '100501'],
    'COACH_NOT_SUBMIT_CONSULT_EXCEPTION'=> ['教练手机号不能一键咨询', '100502'],
    'COACH_MOBILE_NOT_CONSULT_EXCEPTION'=> ['教练手机号不能咨询', '100503'],
    'ALREADY_CONSULTED_EXCEPTION'       => ['不能重复咨询同一个教练', '100504'],
    'CONSULT_MESSAGE_NOT_EXIST'         => ['咨询信息不存在', '100505'],
    'CONSULT_MESSAGE_REMARK_NOT_ANTHEN' => ['您无权备注当前咨询信息', '100506'],
    'SCHSUBMIT_NOT_VALID_ADDR_COACH'    => ['未找到合适距离的教练', '100507'],
    'SCHSUBMIT_NOT_VALID_POINT_COACH'   => ['未找到符合积分条件的教练', '100508'],
    'ALREADY_SUBMIT_WITH_SAME_INFO'     => ['同一个地址只能咨询一次', '100509'],
    'CONSULT_CHANNEL_TYPE_NOT_DEFINED'  => ['外渠线索咨询type类型未知', '100510'],
    'CONSULT_CHANNEL_MOBILE_EXIST'      => ['当前手机号已经咨询过了', '100511'],
    'CONSULT_RECORD_NOT_EXIST'          => ['咨询记录不存在', '100512'],
    'CURRENT_RECORD_RECOMMEND_ALREADY'  => ['当前线索已经推荐过了', '100513'],
    'FILTER_SCHOOL_STAFF_MOBILE'        => ['您可能属于驾校从业者，无法进行学车咨询，感谢对我们的支持', '100514'],
    'RECOMMEND_FIXED_COACH_ERROR'       => ['推荐指定教练失败', '100515'],

    'SUBMIT_COACH_ONLY_THREE_TIMES_ERROR' => ['一键线索只能咨询三次', '100516'],
    'CONSULT_LISTS_ONLY_THREE_TIMES_ERROR' => ['后台线索只能添加三次', '100517'],
    'SCHOOL_CONSULT_LISTS_ONLY_THREE_TIMES_ERROR' => ['同一线索只能添加三次', '100518'],
    'CONSULT_NO_REPEAT_WITHIN_48_HOURS'   => ['48小时内同一线索不可重复提交', '100519'],
    'LOCATION_INFO_NULL_EXCEPTION'        => ['坐标信息为空', '100520'],


    /*****************************发送短信异常code {100600 ~ 100700}*********************************/
    'MESSAGE_TPL_NOT_DEFINED_TYPE'    => ['短信参数类型错误','100600'],
    'MESSAGE_SEND_FAIL'               => ['短信发送失败','100601'],
    'CAPTCHA_NOT_VALID_EXCEPTION'     => ['短信验证码有误','100602'],


    /*****************************考试安排异常code {100700 ~ 100800}*********************************/
    'EXAM_SUBJECT_ALREADY_EXIST'        => ['当天考试科目已经存在', '100700'],
    'EXAM_VID_NOT_FIND_EXCEPTION'       => ['当天考试不存在', '100701'],
    'EXAM_NOT_BELONG_COACH_EXCEPTION'   => ['无权编辑非自己的考试', '100702'],


    /*****************************微名片异常code {100800 ~ 100900}*********************************/
    'CLASS_NOT_FIND_EXCEPTION'          => ['未找到对应的班级信息', '100800'],
    'CLASS_NOT_BELONG_COACH_EXCEPTION'  => ['无权操作非自己创建的班级', '100801'],
    'LOCATION_MORE_THAN_FIVE_EXCEPTION' => ['学车地址不能超过5条', '100802'],
    'TOKEN_OR_OPENID_NOT_FIND_EXCEPTION' => ['未找到对应的教练微名片信息', '100803'],
    'COACH_LABEL_MORE_THAN_SIX'         => ['特色标签不能超过6个', '100804'],
    'COACH_CARD_NOT_COMPLETE_EXCEPTION' => ['微名片没有制作完成', '100805'],
    'COACH_CARD_CLASS_MORE_THAN_TEN_EXCEPTION' => ['微名片班级信息最多添加10个', '100806'],


    /*****************************优惠券异常code {100900 ~ 101000}*********************************/
    'COUPON_EXHAUST_EXCEPTION'          => ['当前现金券已经领完', '100900'],
    'CANNOT_REPEAT_RECEIVE_EXCEPTION'   => ['现金券不可重复领取哦~', '100901'],
    'COUPON_ID_NOT_EXIST_EXCEPTION'     => ['领取现金券失败', '100902'],
    'COUPON_VARIFY_CODE_ERROR'          => ['验证码有误', '100903'],
    'COUPON_ID_NOT_EXIST_EXCEPTION'     => ['优惠券不存在', '100904'],
    'COUPON_TIME_OUT_EXCEPTION'         => ['优惠券已失效', '100905'],
    'COUPON_EXHAUSTE_EXCEPTION'         => ['优惠券已领光', '100906'],
    'COACH_CANOT_ACHIEVE_COUPON_EXCEPTION'   => ['教练不能领取优惠券', '100907'],


    /*****************************优惠券异常code {101000 ~ 101100}*********************************/
    'ACTIVITY_GIFT_ID_NOT_EXHAUST_EXCEPTION'  => ['记录不存在', '101001'],
    'PERMISSION_DENY_FOR_GIFT_REPLY'          => ['你不权答谢该记录', '101002'],


    /*****************************活动code {102000 ~ 102100}*********************************/
    'ACTIVITY_REPEAT_ATTENDED_EXCEPTION'  => ['您已经报名参加该活动', '102001'],
    'ACTIVITY_RECORD_NOT_FIND_EXCEPTION'  => ['未找到对应的活动信息', '102002'],
    'ACTIVITY_STU_RESIGNUP_EXCEPTION'     => ['您已经参加过该活动', '102003'],
    'ACTIVITY_COUPON_EXHAUST_EXCEPTION'   => ['优惠券已经领光了', '102004'],


    /*****************************学员端语音播报code {102200 ~ 102300}*********************************/
    'AUDIO_SHAREID_NOT_FIND_EXCEPTION'    => ['未找到对应的系统信息', '102201'],
    'AUDIO_SYSTEM_NOT_FIND_EXCEPTION'     => ['未找到当前系统信息', '102202'],
    'AUDIO_SYSTEM_DELETE_NOT_AUTHEN_EXCEPTION'     => ['您无权删除该信息', '102203'],
    'AUDIO_SYSTEM_CHANGE_NOT_AUTHEN_EXCEPTION'     => ['您无权切换该信息', '102204'],
    'AUDIO_THIRDPLAT_DATA_NOT_FIND_EXCEPTION'     => ['未找到对应的模板数据', '102205'],

];