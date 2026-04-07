$(function () {
    const bannerSwiper = new Swiper(".bannerSwiper", {
        autoplay: true,
        effect: "fade",
        fadeEffect: {
        crossFade: true,
        },
        on: {
        init: function (swiper) {
            //Swiper初始化了
            $(".style_carousel_item_card__wqto7").removeClass("active");
            $(".wrap-is-me")
            .eq(this.activeIndex)
            .find(".style_carousel_item_card__wqto7")
            .eq(this.activeIndex)
            .addClass("active");
        },
        slideChangeTransitionEnd: function () {
            $(".style_carousel_item_card__wqto7").removeClass("active");
            $(".wrap-is-me")
            .eq(this.activeIndex)
            .find(".style_carousel_item_card__wqto7")
            .eq(this.activeIndex)
            .addClass("active");
        },
        },
    });
    const mobileBannerSwiper = new Swiper(".mobileBannerSwiper", {
        autoplay: true,
        loop: true,
        pagination: {
        el: ".slick-dots",
        clickable: true,
        },
    });

    //   bannerSwiper 移动更换banner
    $(".style_carousel_item_card__wqto7").on("mouseenter", function () {
        const parent = $(this).parent().parent();
        bannerSwiper.slideTo(parent.index());
    });

    // 搜索框打开搜索详情
    $("#searchInput").on("focus", function () {
        $("#searchWrap").css("display", "flex");
        // 如果是移动端，则隐藏logo以及其他元素
        if ($(window).width() < 720) {
        $("#logo").addClass("hidden");
        $("#vip").addClass("hidden");
        $("#back").removeClass("hidden");
        }
    });

    $("#back").on("click", function () {
        $("#logo").removeClass("hidden");
        $("#vip").removeClass("hidden");
        $("#back").addClass("hidden");
        $("#searchWrap").css("display", "none");
    });

    $(document).on("click", function (e) {
        const target = e.target;

        if (
        $(window).width() >= 720 &&
        !target.closest("#searchWrap") &&
        !target.closest("#searchInput")
        ) {
        $("#searchWrap").css("display", "none");
        }
    });

    $(window).on("scroll", function () {
        const scrollTop = $(this).scrollTop();
        if (scrollTop >= 200) {
        $("#header").removeClass("md:bg-transparent");
        $("#backTop").removeClass("d-none");
        } else {
        $("#header").addClass("md:bg-transparent");
        $("#backTop").addClass("d-none");
        }
    });

    $("#backTop").on("click", function () {
        $("html, body").animate({ scrollTop: 0 }, 200);
    });

    function moveExtraTabs(deep) {
        var fixedWidth = 68.4375;
        var moreDropdown = $("#moreDropdown");
        var moreDropdownWrap = $("#moreDropdownWrap");
        var moreMenu = $("#moreMenu");

        var menuTabWidth = $("#menu a.header-tab").width();
        if (menuTabWidth < fixedWidth) {
        // 如果标签宽度小于固定宽度，将标签移动到下拉菜单中
        var lastMenuTab = $("#menu > a.header-tab:last");
        var dropdownItem = $("<div>", {
            class: "w-32 flex flex-col cursor-pointer py-2 items-center",
            id: lastMenuTab.attr("id"),
        });
        var a = $("<a>", {
            class:
            "text-yellow-hover transition-colors duration-300 truncate text-white",
            text: lastMenuTab.text(),
            href: lastMenuTab.attr("href"),
        });
        dropdownItem.append(a);
        moreDropdownWrap.append(dropdownItem);
        lastMenuTab.remove();
        if (deep) {
            return;
        }
        moveExtraTabs(false);
        } else {
        if (moreDropdownWrap.children().length > 0) {
            // 如果标签宽度大于固定宽度，将下拉标签中最后一个移动到菜单中
            var dropdownItemLast = moreDropdownWrap.children().last();
            var menuItem = $("<a>", {
            class: "flex flex-1 flex-col items-center cursor-pointer header-tab",
            href: dropdownItemLast.find("a").attr("href"),
            });
            var span = $("<span>", {
            class:
                "text-yellow-hover transition-colors duration-300 truncate text-white",
            text: dropdownItemLast.find("a").text(),
            });
            menuItem.append(span);
            moreMenu.before(menuItem);
            dropdownItemLast.remove();
            moveExtraTabs(true);
        }
        return;
        }

        // 如果下拉菜单中有内容，显示更多按钮，否则隐藏
        if (moreDropdownWrap.children().length > 0) {
        moreMenu.show();
        } else {
        moreMenu.hide();
        }
    }

    // 初始调用
    moveExtraTabs();

    // 监听窗口大小变化事件
    $(window).on("resize", function () {
        moveExtraTabs();
    });

    // 显示和隐藏更多下拉菜单
    $("#moreMenu").click(function () {
        $("#moreDropdown").toggleClass("hidden");
    });

    // 如果需要点击外部关闭下拉菜单，可以添加以下代码
    $(document).click(function (event) {
        if (!$(event.target).closest("#more-button, .more-dropdown").length) {
        $(".more-dropdown").hide();
        }
    });

    // play页
    // 简介展示
    $("#openVideoIntro").on("click", function () {
        $("#videoInfo").hide();
        $("#videoIntro").show();
    });
    // 简介隐藏
    $("#videoIntroBack").on("click", function () {
        $("#videoInfo").show();
        $("#videoIntro").hide();
    });
    // 视频源左滑动
    $("#trackToLeft").on("click", function () {
        var videoSourceTrack = $("#videoSourceTrack");
        var scollLeft = videoSourceTrack.scrollLeft();
        var skewWidth = videoSourceTrack.find("#vodSource-1").outerWidth(true);
        videoSourceTrack.scrollLeft(scollLeft - skewWidth);
        if (videoSourceTrack.scrollLeft() <= 0) {
        $(this).find("img").addClass("transparent");
        }
        $("#trackToRight").find("img").removeClass("transparent");
    });
    // 视频源右滑动
    $("#trackToRight").on("click", function () {
        var videoSourceTrack = $("#videoSourceTrack");
        var scollLeft = videoSourceTrack.scrollLeft();
        var skewWidth = videoSourceTrack.find("#vodSource-1").outerWidth(true);
        videoSourceTrack.scrollLeft(scollLeft + skewWidth);
        if (
        videoSourceTrack.scrollLeft() >=
        videoSourceTrack[0].scrollWidth - videoSourceTrack[0].offsetWidth
        ) {
        $(this).find("img").addClass("transparent");
        }
        $("#trackToLeft").find("img").removeClass("transparent");
    });
    const videoLength = $("#videoList").children().length
    $("#episodeCountEnd").text(
    videoLength >= 20
        ? "20"
        : videoLength
    );
    $(".videoEndLeavl").text($("#episodeCountEnd").text());
    if (videoLength < 2) {
        $("#mobileVideoGroup-selected").hide()
    }
    $("#videoSelected").on("click", function () {
        $("#selectedList").toggleClass("hidden");
    });
    // 初始化pc视频播放列表
    function initVideoList() {
        // 偏移集数
        const skewLeave = 20;
        const selectedLength = Math.ceil(
        $("#videoList").children().length / skewLeave
        );
        if (selectedLength > 1) {
        // 大于20集的不显示
        $("#videoList").children().slice(skewLeave).hide();
        }
        for (let i = 0; i < selectedLength; i++) {
        const li = $("<li>", {
            class:
            i === 0
                ? "style_radioOptionCard__ON4rV style_selectedOptionDropdownGroup__Na2am"
                : "style_radioOptionCard__ON4rV cursor-pointer",
            style: "width: 118px; margin-bottom: 0px;",
        });

        const label = $("<label>", {
            for: "episodeGroup-0",
            class: "flex flex-row space-x-1 cursor-pointer",
        });

        const span = $("<span>", {
            class: "text-sm",
            text: i * 20 + 1,
        });

        const span2 = $("<span>", {
            class: "text-sm",
            text: "-",
        });

        let span3 = $("<span>", {
            class: "text-sm",
            text: selectedLength <= 1 ? $("#episodeCountEnd").text() : (i + 1) * 20,
        });
        if (i === selectedLength - 1) {
            const lastText = $("#videoList").children().last().find("a").text()
            const splitText = lastText.replace(/[^0-9]/ig, '')
            span3 = $("<span>", {
            class: "text-sm",
            text: splitText,
            });
        }

        const span4 = $("<span>", {
            class: "text-sm",
            text: "",
        });

        label.append(span, span2, span3, span4);

        li.append(label);

        li.on("click", function () {
            $(this).addClass("style_selectedOptionDropdownGroup__Na2am");
            $(this)
            .siblings()
            .removeClass("style_selectedOptionDropdownGroup__Na2am");

            const i = $(this).index();
            // 展示选中的集数
            $("#videoList").children().hide();
            $("#videoList")
            .children()
            .slice(i * skewLeave, (i + 1) * skewLeave)
            .show();

            // 隐藏选集列表
            $("#selectedList").toggleClass("hidden");
            // 更新选集下拉菜单展示
            $("#episodeCountStart").text(span.text());
            $("#episodeCountEnd").text(span3.text());
        });

        $("#selectedList").append(li);
        }
    }

    initVideoList();
    initMobileVideoList();

    //  初始化移动端视频列表
    function initMobileVideoList() {
        // 偏移集数
        const skewLeave = 20;
        const selectedLength = Math.ceil(
        $("#mobileVideoWrap").children().length / skewLeave
        );
        if (selectedLength > 1) {
        // 大于20集的不显示
        $("#mobileVideoWrap").children().slice(skewLeave).hide();
        $('#mobileModalVideoWrap').children().slice(skewLeave).hide();
        }
        for (let i = 0; i < selectedLength; i++) {
            const li = $("<li>", {
                class:
                i === 0
                    ? "style_radioOptionCard__ON4rV style_selectedOptionDetailsCard__aACQa"
                    : "style_radioOptionCard__ON4rV style_unselectedOptionDetailsCard__VSu96",
            });
            const label = $("<label>", {
                class: "flex flex-row space-x-1",
            });
        
            const span = $("<span>", {
                class: "text-sm",
                text: i * 20 + 1,
            });
        
            const span2 = $("<span>", {
                class: "text-sm",
                text: "-",
            });
        
            let span3 = $("<span>", {
                class: "text-sm",
                text: (selectedLength <= 1 ? $("#episodeCountEnd").text() : (i + 1) * 20) + '集',
            });
            if (i === selectedLength - 1) {
                const lastText = $("#mobileModalVideoWrap").children().last().find("a").text()
                const splitText = lastText.replace(/[^0-9]/ig, '')
                span3 = $("<span>", {
                class: "text-sm",
                text: splitText + '集',
                });
            }
        
            const span4 = $("<span>", {
                class: "text-sm",
                text: "",
            });
        
            label.append(span, span2, span3, span4);
        
            li.append(label);
            
            li.on("click", function () {
                $(this).removeClass("style_unselectedOptionDetailsCard__VSu96");
                $(this).addClass("style_selectedOptionDetailsCard__aACQa");
                $(this)
                .siblings()
                .removeClass("style_selectedOptionDetailsCard__aACQa")
                .addClass("style_unselectedOptionDetailsCard__VSu96");
        
                const i = $(this).index();
                // 展示选中的集数
                $("#mobileModalVideoWrap").children().hide();
                $("#mobileModalVideoWrap")
                .children()
                .slice(i * skewLeave, (i + 1) * skewLeave)
                .show();
                $("#mobileVideoWrap").children().hide();
                $("#mobileVideoWrap")
                .children()
                .slice(i * skewLeave, (i + 1) * skewLeave)
                .show();
                
                // 更新选集下拉菜单展示
                $(".videoStartLeavl").text(span.text().replace('集', ''));
                $(".videoEndLeavl").text(span3.text().replace('集', ''));
            });
            
            $('#modalVideoSelected').append(li)
        }
    }

    $("#mobileVideoGroup-selected").on("click", function () {
        $("#videoMobileList").show({ duration: 300 });
    });
    $("#videoMobileListModal").on("click", function () {
        $("#videoMobileList").hide({ duration: 300 });
    });

    $("#openMobileIntro").on("click", function () {
        $("#videoMobileIntro").show({ duration: 300 });
    });
    $("#videoMobileIntroModal").on("click", function () {
        $("#videoMobileIntro").hide({ duration: 300 });
    });
    $("#closeVideoMobileIntro").on("click", function () {
        $("#videoMobileIntro").hide({ duration: 300 });
    });

    $('.backPage').on('click', function () {
        window.history.back();
    })

    $('#download').hover(  
        function() {  
            $('#downloadbox').show();  
        },   
        function() {  
            $('#downloadbox').hide(); 
        }  
    );  

    $('.share-icn').click(function() {  
        event.stopPropagation(); 
        $('.share-box-bg').removeClass('hidden').addClass('block');  
        $('.share-box').removeClass('hidden').addClass('block');  
    }); 

    $(document).click(function(event) {  
        if (!$(event.target).closest('.share-box-center').length) {  
            $('.share-box-bg').removeClass('block').addClass('hidden');  
            $('.share-box').removeClass('block').addClass('hidden');  
        }  
    });  

    $('.copy-btn').click(function() {  
        const $tempInput = $('<input>');  
        $('body').append($tempInput);  
        $tempInput.val($('#share-text').text()).select();  
        document.execCommand('copy');  
        $tempInput.remove();  

        $('#message').fadeIn().delay(1000).fadeOut();  
        $('.share-box-bg').removeClass('block').addClass('hidden');  
        $('.share-box').removeClass('block').addClass('hidden');  
    });  

});
