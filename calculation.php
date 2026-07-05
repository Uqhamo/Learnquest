<?php
session_start();
if(isset($_POST['operation'])){
    $_SESSION['operation'] =$_POST['operation'];
}
$operation = $_SESSION['operation']??'addition';
$message = "";
$message_class = "";
$show_correct = false;
$correct_choice =0;
$tip = "";
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['user_answer'])){
    $user_choice = (int)$_POST['user_answer'];
    $correct_choice = (int)$_SERVER['correct_answer'];
    $_SESSION['correct_answer'];

    if($user_choice === $correct_choice){
        $message = "Correct! Well done";
        $message_class ="correct";
    }else{
        $message="The answer is Wrong, You chose <strong>{$user_choice}</strong>";
        $message_class = "wrong";
        $show_correct = true;

        $last_op = $_SESSION['last_operator']??'+';
        $tips = [
            '+'=> "Tip: Addition means combining two numbers together",
            '-'=>"Tip: Subtraction means taking one number away from another",
            'x'=>"Tip:Multiplication is adding the same number multiple times",
            '÷' => "Tip: Division means splitting a number into equal groups.",
        ];
        $tip = $tips[$last_op]??"";
    }
}
if($operation==='addition'){
    $operator = '+' ;
 }else if($operation==='subtration'){
    $operator ='-';
 }else if($operation==='multiplication'){
    $operator ='*';
 }else if($operation==='division'){
    $operator ='/';
 }
 else{
    $all_ops = ['+','-','*','/'];
    $operator = $all_ops[rand(0,3)];
 }
 if($operator === '+'){
$num1 = rand(1,100);
$num2 = rand(1,100);
$correct = $num1 + $num2;
 }
 else if($operator === '-'){
$num1 = rand(1,100);
$num2 = rand(1,$num1);
$correct = $num1 - $num2;
 }
 else if($operator === '*'){
$num1 = rand(2,12);
$num2 = rand(2,12);
$correct = $num1 * $num2;
 }
 else{
$num2 = rand(2,12);
$num1 = $num2*rand(1,12);
$correct = $num1 / $num2;
 }
 

 
 $_SESSION['correct_answer'] = $correct;
 $_SESSION['last_operator'] =$operator;

 $spread = ($operator ==='*')?20:10;

 $options =[$correct];
 while(count($options)<4){
    $wrong = $correct + rand(-$spread,$spread);
    if($wrong!==$correct&&$wrong>0&&!in_array($wrong,$options)){
        $options[] = $wrong;
    }
 }
 shuffle($options);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="quiz-container">
        <a class="back-link" href="dashboard.php">Menu</a>
         <?php if($message):?>
            <div class ="feedback"<?php echo $message_class;?>">
                <?php echo $message;?>
    </div>
    <?php endif;?>
    <?php if ($show_correct):?>
        <div class="correct-reveal">
            The correct answer was:
            <span><?php echo$correct_choice;?></span>
            Keep practising - you'll get it next time!
        </div>
        <?php if ($tip): ?>
            <div class="tip-box"><?php echo $tip; ?></div>
        <?php endif; ?>
    <?php endif; ?>
    
        
    

    <h2>Solve the Equation</h2>
    <div class ="equation"><?php echo "$num1 $operator $num2=?";?>

    </div>
    <form method="POST" action="dashboard.php">
<?php foreach ($options as $option):?>
    <button type="submit" name="user_answer" value="<?php echo $option;?>" class="btn"> <?php echo $option;?></button>
    <?php endforeach;?>
    </form>
    <br>
    <button onclick="window.location.reload();" class = "btn next-btn">Skip Question</button>
    </div>
</body>
</html>