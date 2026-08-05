<?php
require_once __DIR__ . '/../inc/includes.php';
session_start();
// Dummy user
$_SESSION['user_id'] = 'test';

include_once __DIR__ . '/../myphp/_header_modern.php';
get_header_modern('Test Modal', '');
?>
<div class="leads-page" style="padding: 100px;">
    <h2>Test Modal visibility</h2>
    <div class="lead-actions">
        <button id="btn-test" class="btn btn-primary btn-lead-contact" title="Hazte VIP para contactar" onclick="$('#modal-vip-upgrade').modal('show')">
            <i class="fas fa-comment"></i> Chatear Test
        </button>
    </div>
</div>

<!-- Modal VIP Upgrade -->
<div id="modal-vip-upgrade" class="modal" tabindex="-1" role="dialog" style="z-index: 999999;">
  <div class="modal-dialog" role="document" style="z-index: 999999; position: relative;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" style="color: black;">&times;</button>
        <h4 class="modal-title"><i class="fas fa-crown"></i> VENTAJAS VIP</h4>
      </div>
      <div class="modal-body" style="padding: 30px; text-align: center; color: black;">
        <p style="font-size: 18px; margin-bottom: 25px;">Contactar directamente con los leads es una función exclusiva para usuarios VIP. <br><strong>¡Hazte VIP y contacta sin límites!</strong></p>
      </div>
    </div>
  </div>
</div>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
