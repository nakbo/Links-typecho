<?php
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8" role="main">
                <form method="post" name="manage_categories" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i>
                                <input type="checkbox" class="typecho-table-select-all"/>
                            </label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i
                                            class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i
                                            class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些链接吗?'); ?>"
                                           href="<?php $options->index('/action/links?do=delete'); ?>"><?php _e('删除'); ?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="20"/>
                                <col width="10%"/>
                                <col width="25%"/>
                                <col/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('图片'); ?></th>
                                <th><?php _e('名称'); ?></th>
                                <th><?php _e('地址'); ?></th>
                                <th><?php _e('分类'); ?></th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php if (empty($links = $db->fetchAll($db->select()->from('table.links')->order('table.links.order')))): ?>
                                <tr>
                                    <td colspan="5"><h6 class="typecho-list-table-title"><?php _e('没有任何链接'); ?></h6>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($links as $link): ?>
                                    <tr id="lid-<?= $link['lid']; ?>">
                                        <td><input type="checkbox" value="<?= $link['lid']; ?>" name="lid[]"/>
                                        </td>
                                        <td><?php if ($link['image']) {
                                                echo '<a href="' . $link['image'] . '" title="点击放大" target="_blank">
                                                <img class="avatar" src="' . $link['image'] . '" alt="' . $link['name'] . '" width="32" height="32"/></a>';
                                            } ?></td>
                                        <td><a href="<?= $request->makeUriByRequest('lid=' . $link['lid']); ?>"
                                               title="点击编辑"><?= $link['name']; ?></a>
                                        <td><?= $link['url']; ?></td>
                                        <td><?= $link['sort']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="col-mb-12 col-tb-4" role="form">
                <?php Links_Plugin::form($request->lid)->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        $(document).ready(function () {
            let table = $('.typecho-list-table').tableDnD({
                onDrop: function () {
                    let ids = [];

                    $('input[type=checkbox]', table).each(function () {
                        ids.push($(this).val());
                    });

                    $.post('<?php $options->index('/action/links?do=sort'); ?>', $.param({lid: ids}));

                    $('tr', table).each(function (i) {
                        if (i % 2) {
                            $(this).addClass('even');
                        } else {
                            $(this).removeClass('even');
                        }
                    });
                }
            });

            table.tableSelectable({
                checkEl: 'input[type=checkbox]',
                rowEl: 'tr',
                selectAllEl: '.typecho-table-select-all',
                actionEl: '.dropdown-menu a'
            });

            $('.btn-drop').dropdownMenu({
                btnEl: '.dropdown-toggle',
                menuEl: '.dropdown-menu'
            });

            $('.dropdown-menu button.merge').click(function () {
                let btn = $(this);
                btn.parents('form').attr('action', btn.attr('rel')).submit();
            });

            <?php if (isset($request->lid)): ?>
            $('.typecho-mini-panel').effect('highlight', '#AACB36');
            <?php endif; ?>
        });
    })();
</script>
<?php include 'footer.php'; ?>
