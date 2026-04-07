var body = $('body');
body.on('keyup', 'form', function (e) {
    var jthis = $(this);
    if ((e.ctrlKey && (e.which == 13 || e.which == 10)) || (e.altKey && e.which == 83)) {
        jthis.trigger('submit');
        return false;
    }
});


$.alert = function (body, timeout, options) {
    var options = options || {size: "md"};
    var s = '\
	 <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">\
        <div class="modal-dialog modal-dialog-centered text-center " role="document">\
            <div class="modal-content tx-size-sm">\
                <div class="modal-body text-center p-4 pb-5">\
                    <button aria-label="Close" class="btn-close position-absolute" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>\
                    <i class="fa fa-check-square fs-70 text-success lh-1 my-5 d-inline-block"></i>\
					<h4 class="text-success tx-semibold">\
					 ' + body + '\
					</h4>\
                    <button aria-label="Close" class="btn btn-danger pd-x-25" data-bs-dismiss="modal">关闭</button>\
                </div>\
            </div>\
        </div>\
    </div>';
    var jmodal = $(s).appendTo('body');
    jmodal.modal('show');
    if (typeof timeout != 'undefined' && timeout >= 0) {
        setTimeout(function () {
            jmodal.modal('dispose');
        }, timeout * 1000);
    } 
    return jmodal;
};

var jsearch = $("#search");
var jkeyword = $('#keyword');
jsearch.on('click', function(){
	var keyword = $("#keyword").val();
	window.location = xn.url('vod-search', {keyword:xn.urlencode(keyword)}, true);
});
jkeyword.on('keydown', function(e) {
	if(e.keyCode == 13) jsearch.trigger('click');
});
/*选中所有 / check all */
 body.on('click', 'input.checkall', function () {
    var jthis = $(this);
    var target = jthis.data('target');
    jtarget = $(target);
    jtarget.prop('checked', this.checked);
});










