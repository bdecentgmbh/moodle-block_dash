define(['jquery'], function($) {

    function getSliderSelector(uniqueid) {
        return '.card_layout_slider.slider-' + uniqueid;
    }

    function getGridSelector(uniqueid) {
        return '.card-layout-' + uniqueid + '.card-layout-default';
    }

    function getGridCards(uniqueid) {
        return $(getGridSelector(uniqueid)).children('.card-block.default');
    }

    function getGridDetailPair($card) {
        var $next = $card.next();
        if ($next.length && $next.hasClass('details-area-block')) {
            return $next;
        }
        return $();
    }

    function applySliderSearch(uniqueid) {
        var $slider = $(getSliderSelector(uniqueid));
        var $search = $('.slider-quicksearch-' + uniqueid);

        if (!$slider.length || !$slider.hasClass('slick-initialized')) {
            return;
        }

        var term = ($search.val() || '').toLowerCase();

        $slider.slick('slickUnfilter');

        if (!term) {
            return;
        }

        $slider.slick('slickFilter', function() {
            var text = ($(this).text() || '').toLowerCase();
            return text.indexOf(term) !== -1;
        });
    }

    function applySliderSort(uniqueid, direction) {
        var $slider = $(getSliderSelector(uniqueid));

        if (!$slider.length || !$slider.hasClass('slick-initialized')) {
            return;
        }

        $slider.slick('slickUnfilter');

        var slickObject = $slider.slick('getSlick');
        var slickSettings = $.extend(true, {}, slickObject.originalSettings);

        $slider.slick('unslick');

        var $slides = $slider.children('.card-block.slider');

        $slides.sort(function(a, b) {
            var aVal = parseInt($(a).attr('data-recordid'), 10) || 0;
            var bVal = parseInt($(b).attr('data-recordid'), 10) || 0;

            if (direction === 'desc') {
                return bVal - aVal;
            }

            return aVal - bVal;
        });

        $slider.append($slides);
        $slider.slick(slickSettings);

        applySliderSearch(uniqueid);
    }

    function initSlider(uniqueid) {
        var $search = $('.slider-quicksearch-' + uniqueid);
        var $sorter = $('.slider-sorter-' + uniqueid);

        if ($search.length) {
            $search.on('keyup input', function() {
                applySliderSearch(uniqueid);
            });
        }

        if ($sorter.length) {
            $sorter.on('click', 'button', function(e) {
                e.preventDefault();

                $sorter.find('button').removeClass('active');
                $(this).addClass('active');

                var direction = $(this).hasClass('desc') ? 'desc' : 'asc';
                applySliderSort(uniqueid, direction);
            });
        }
    }

    function applyGridSearch(uniqueid) {
        var term = ($('.grid-quicksearch-' + uniqueid).val() || '').toLowerCase();

        getGridCards(uniqueid).each(function() {
            var $card = $(this);
            var $detail = getGridDetailPair($card);
            var text = ($card.text() || '').toLowerCase();
            var matched = !term || text.indexOf(term) !== -1;

            $card.toggle(matched);

            if ($detail.length) {
                $detail.toggle(matched);
                if (!matched) {
                    $detail.removeClass('show-detail');
                }
            }
        });
    }

    function applyGridSort(uniqueid, direction) {
        var $container = $(getGridSelector(uniqueid));

        if (!$container.length) {
            return;
        }

        var units = [];

        getGridCards(uniqueid).each(function() {
            var $card = $(this);
            units.push({
                card: $card,
                detail: getGridDetailPair($card),
                value: parseInt($card.attr('data-recordid'), 10) || 0
            });
        });

        units.sort(function(a, b) {
            if (direction === 'desc') {
                return b.value - a.value;
            }
            return a.value - b.value;
        });

        units.forEach(function(unit) {
            $container.append(unit.card);
            if (unit.detail.length) {
                $container.append(unit.detail);
            }
        });

        applyGridSearch(uniqueid);
    }

    function initGrid(uniqueid) {
        var $search = $('.grid-quicksearch-' + uniqueid);
        var $sorter = $('.grid-sorter-' + uniqueid);

        if ($search.length) {
            $search.on('keyup input', function() {
                applyGridSearch(uniqueid);
            });
        }

        if ($sorter.length) {
            $sorter.on('click', 'button', function(e) {
                e.preventDefault();

                $sorter.find('button').removeClass('active');
                $(this).addClass('active');

                var direction = $(this).hasClass('desc') ? 'desc' : 'asc';
                applyGridSort(uniqueid, direction);
            });
        }
    }

    function init(uniqueid) {
        initSlider(uniqueid);
        initGrid(uniqueid);
    }

    return {
        init: init
    };
});