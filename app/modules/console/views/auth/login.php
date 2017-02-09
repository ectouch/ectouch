<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'ECTouch管理中心';
?>
<div class="login">
		<div class="login-con">
			<header>
				<div class="login-header"><?= Html::encode($this->title) ?></div>
			</header>
			<section>
        <?php $form = ActiveForm::begin(['id' => 'login-form', 'fieldConfig' => ['template' => "{error}\n{input}"]]); ?>
				<div class="login-input">
					<?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => '管理员用户名']) ?>
					<?= $form->field($model, 'password')->passwordInput(['placeholder' => '管理员密码']) ?>
					<div class="user-info">
						<div class="save">
              <?= $form->field($model, 'rememberMe')->checkbox() ?>
						</div>
						<div class="forget">
							<?= Html::a('忘记密码？', ['forgot']) ?>
						</div>
					</div>
					<?= Html::submitButton('登录管理中心', ['class' => 'btn btn-primary mange', 'name' => 'login-button']) ?>
				</div>
				<?php ActiveForm::end(); ?>
			</section>
		</div>
	</div>	
