<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include VIEW_PATH . 'templates/head.php'; ?>
  
  <title>商品一覧</title>
  <link rel="stylesheet" href="<?php print h(STYLESHEET_PATH . 'index.css'); ?>">
</head>
<body>
  <?php include VIEW_PATH . 'templates/header_logined.php'; ?>
  

  <div class="container">
    <h1>商品一覧</h1>
    <?php include VIEW_PATH . 'templates/messages.php'; ?>

    <div class="row space-between">
      <?php if($current_page > 1){ ?>
        <form class="mr-2" method="get">
          <input type="submit" value="前へ" class="btn btn-secondary">
          <input type="hidden" name="current_page" value="<?php print h($current_page - 1) ?>">
        </form>
      <?php } ?>
      <?php for ($i = 1; $i <= $total_pages_number; $i++){ ?>
        <form class="mr-2" method="get">
          <?php if($current_page === $i){ ?>
            <input type="submit" value="<?php print h($i) ?>" class="btn btn-info">
          <?php } else { ?>
            <input type="submit" value="<?php print h($i) ?>" class="btn btn-secondary">
          <?php } ?>
          <input type="hidden" name="current_page" value="<?php print h($i) ?>">
        </form>
      <?php } ?>
      <?php if($current_page < $total_pages_number){ ?>
        <form class="mr-2" method="get">
          <input type="submit" value="次へ" class="btn btn-secondary">
          <input type="hidden" name="current_page" value="<?php print h($current_page + 1) ?>">
        </form>
      <?php } ?>
    </div>
    <p><?php print h($items_count_text) ?></p>

    <div class="card-deck">
      <div class="row">
      <?php foreach($items as $item){ ?>
        <div class="col-6 item">
          <div class="card h-100 text-center">
            <div class="card-header">
              <?php print h($item['name']); ?>
            </div>
            <figure class="card-body">
              <img class="card-img" src="<?php print h(IMAGE_PATH . $item['image']); ?>">
              <figcaption>
                <?php print h(number_format($item['price'])); ?>円
                <?php if($item['stock'] > 0){ ?>
                  <form action="index_add_cart.php" method="post">
                    <input type="submit" value="カートに追加" class="btn btn-primary btn-block">
                    <input type="hidden" name="csrf_token" value="<?php print h($token); ?>">
                    <input type="hidden" name="item_id" value="<?php print h($item['item_id']); ?>">
                  </form>
                <?php } else { ?>
                  <p class="text-danger">現在売り切れです。</p>
                <?php } ?>
              </figcaption>
            </figure>
          </div>
        </div>
      <?php } ?>
      </div>
    </div>
  </div>
  
</body>
</html>