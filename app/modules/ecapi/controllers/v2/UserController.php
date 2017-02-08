<?php

namespace app\modules\ecapi\controllers;

use Yii;
use app\models\ecapi\Member;
use app\models\ecapi\RegFields;
use app\models\ecapi\Features;

class UserController extends BaseController
{

    /**
     * POST /user/weixinMiniProgramLogin
     */
    public function actionWeixinMiniProgramLogin()
    {
        $requests = $this->request->post();

        if (isset($requests['userinfo'])) {
            $userinfo = array_change_key_case($requests['userinfo']);
            $this->request->setBodyParams($userinfo);
        }

        $rules = [
            // [['nickname', 'gender', 'language', 'city', 'province', 'country', 'avatarurl', 'code'], 'required'],
            [['code'], 'required'],
            // ['gender', 'string', 'min' => 6, 'max' => 20]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::weixinMiniProgramLogin($this->validated);

        return $this->json($data);
    }

    /**
     * POST /user/signin
     */
    public function actionSignin()
    {
        $rules = [
            // 'username' => 'required|string',
            // 'password' => 'required|min:6|max:20'

            [['username', 'password'], 'required'],
            ['username', 'string'],
            ['password', 'string', 'min' => 6, 'max' => 20]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::login($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/signup-email
     */
    public function actionSignupByEmail()
    {
        $rules = [
            // 'device_id' => 'string',
            // 'username' => 'required|min:3|max:25|alpha_num',
            // 'email' => 'required|email',
            // 'password' => 'required|min:6|max:20',

            [['username', 'email', 'password'], 'required'],
            ['device_id', 'string'],
            ['username', 'string', 'min' => 3, 'max' => 25],
            ['email', 'email'],
            ['password', 'string', 'min' => 6, 'max' => 20],
        ];

        if ($res = Features::check('signup.default')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::createMember($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/signup-mobile
     */
    public function actionSignupByMobile()
    {
        if ($res = Features::check('signup.mobile')) {
            return $this->json($res);
        }

        $rules = [
            // 'device_id' => 'string',
            // 'password' => 'required|min:6|max:20',
            // 'mobile' => 'required|string',
            // 'code' => 'required|string|digits:6',

            [['password', 'mobile', 'code'], 'required'],
            ['device_id', 'string'],
            ['password', 'string', 'min' => 6, 'max' => 20],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
            ['code', 'string', 'min' => 6, 'max' => 6],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::createMemberByMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/verify-mobile
     */
    public function actionVerifyMobile()
    {
        $rules = [
            // 'mobile' => 'required|string',

            ['mobile', 'required'],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::verifyMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/send-code
     */
    public function actionSendCode()
    {
        $rules = [
            // 'mobile' => 'required|string',

            ['mobile', 'required'],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::sendCode($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/profile
     */
    public function actionProfile()
    {
        $data = Member::getMemberByToken();
        return $this->json($data);
    }

    /**
     * POST /user/update-profile
     */
    public function actionUpdateProfile()
    {
        $rules = [
            // 'values' => 'json',
            // 'nickname' => 'string|max:25',
            // 'gender' => 'integer|in:0,1,2',
            // 'avatar_url' => 'string  ',

            ['values', 'json'],
            ['nickname', 'string', 'max' => 25],
            ['gender', 'in', 'range' => [0, 1, 2]],
            ['avatar_url', 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updateMember($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/update-password
     */
    public function actionUpdatePassword()
    {
        $rules = [
            // 'old_password' => 'required|min:6|max:20',
            // 'password' => 'required|min:6|max:20',

            [['old_password', 'password'], 'required'],
            [['old_password', 'password'], 'string', 'min' => 6, 'max' => 20],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updatePassword($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/reset-password-mobile
     */
    public function actionResetPasswordByMobile()
    {
        $rules = [
            // 'mobile' => 'required|string',
            // 'code' => 'required|string|digits:6',
            // 'password' => 'required|min:6|max:20',

            [['mobile', 'code', 'password'], 'required'],
            ['mobile', 'match', 'pattern' => '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'],
            ['code', 'string', 'min' => 6, 'max' => 6],
            ['password', 'string', 'min' => 6, 'max' => 20],
        ];

        if ($res = Features::check('findpass.default')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updatePasswordByMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/reset-password-email
     */
    public function actionResetPasswordByEmail()
    {
        $rules = [
            // 'email' => 'required|email',

            ['email', 'required'],
            ['email', 'email'],
        ];

        if ($res = Features::check('findpass.default')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::resetPassword($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/auth
     */
    public function actionAuth()
    {
        $rules = [
            // 'device_id' => 'string',
            // 'vendor' => 'required|integer|in:1,2,3,4',
            // 'access_token' => 'required|string',
            // 'open_id' => 'required|string',

            [['vendor', 'access_token', 'open_id'], 'required'],
            ['device_id', 'required'],
            ['vendor', 'in', 'range' => [1, 2, 3, 4]],
            [['access_token', 'open_id'], 'string'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::auth($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.user.profile.fields
     */
    public function actionFields()
    {
        $data = RegFields::findAll();
        return $this->json($data);
    }

    /**
     * GET /user/web
     */
    public function actionWebOauth()
    {
        $rules = [
            // 'vendor' => 'required|integer|in:1,2,3,4',
            // 'referer' => 'required|url',

            [['vendor', 'referer'], 'required'],
            ['vendor', 'in', 'range' => [1, 2, 3, 4]],
            ['referer', 'url'],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::webOauth($this->validated);
        if (isset($data['error'])) {
            return $this->json($data);
        }
        return redirect($data);
    }

    /**
     * GET /ecapi.auth.web.callback/:vendor
     */
    public function actionWebCallback($vendor)
    {
        $data = Member::webOauthCallback($vendor);
        if (isset($data['error'])) {
            return $this->json($data);
        }

        if (isset($_GET['referer'])) {
            return redirect(urldecode($_GET['referer']) . '?token=' . $data['token'] . '&openid=' . $data['openid']);
        }
        return $this->json(['token' => $data]);
    }

}
