define([], function () {

    var uniqueID;

    const classList = { // Boostrap class gird size : elements count in row.
        12: 1,
        6: 2,
        4: 3,
        3: 4,
        25: 5, // Custom size.
        2: 6,
        1: 12
    };

    var updateColumns = {
        12: 12,
        6: 8,
        4: 8,
        3: 8,
        25: 8, // Custom size.
        2: 8,
        1: 8
    };


    var Block;

    var Columns; // Bootstrap class.

    var SELECTORS = {
        block: ".card-block-parent"
    };

    const expandDetails = () => {
        Block = document.querySelector('.card-layout-' + uniqueID);

        if (Block === null) {
            return;
        }

        if (Columns == 12) {
            return;
        }

        var cards = Block.querySelectorAll(SELECTORS.block);
        if (cards !== null) {

            cards.forEach((card) => {
                card.addEventListener('click', (e) => {
                    var target = e.currentTarget;

                    if (target.classList.contains('expand-details')) {
                        removeClass(target);
                        resetOtherClass(target);
                        target.classList.add('col-sm-' + Columns.toString());
                        return;
                    }
                    // TODO: Reset to current class.
                    resetOtherClass(target);
                    // Remove preivous class.
                    target.classList.forEach((htmlClass) => {
                        if (htmlClass.startsWith('col-sm')) {
                            target.classList.remove(htmlClass);
                        }
                    });
                    // Add expanded class.
                    var newClass = updateColumns[Columns] || 8;
                    target.classList.add('col-sm-' + newClass.toString());

                    var index = Array.from(target.parentNode.children).indexOf(target);
                    var position = parseInt(index) + 1;

                    var classListColumn = parseInt(classList[Columns.toString()]);
                    // Find the target position in the row.
                    var Row = parseInt(position % parseInt(classListColumn));
                    var positionRow = Row == 0 ? classListColumn : Row;
                    // Find the center postion, so, we decide its increment or decrement.
                    var prevElement = parseInt(positionRow - 1);

                    // If the target is last element in the row.
                    // Need to extend the space to previous items.
                    if (Columns == 1) {
                        var isDown = (((prevElement * 1) + updateColumns[Columns]) > 12);
                        if (isDown) {
                            var startIndex = (index - prevElement);
                            var previousElements = Array.from(target.parentNode.children).slice(startIndex, index);
                            var expandClass = (prevElement == 5) ? 25 : 2;
                            previousElements.forEach((prev) => {
                                removeClass(prev);
                                prev.classList.add('col-sm-' + expandClass.toString());
                            });
                        }
                        return;
                    }

                    var isDown = (((prevElement * 2) + updateColumns[Columns]) > 12);
                    // Adjust current row.
                    if (Columns >= 2 && !isDown) {
                        if (prevElement >= 1) {
                            var expandClass = (12 - updateColumns[Columns]) / prevElement;
                            var startIndex = (index - prevElement);
                            var previousElements = Array.from(target.parentNode.children).slice(startIndex, index);
                            previousElements.forEach((prev) => {
                                removeClass(prev);
                                prev.classList.add('col-sm-' + expandClass.toString());
                            });
                        } else {
                            var remain = (12 - updateColumns[Columns] + Columns);
                            if (remain > 0) {

                                var nextElements = Array.from(target.parentNode.children).slice(index + 1, index + 2);
                                nextElements.forEach((prev) => {
                                    removeClass(prev);
                                    prev.classList.add('col-sm-4');
                                });
                            }

                        }
                    } else {
                        // Adjust previous row.
                        var expandClass = Math.ceil(12 / prevElement);
                        // Make the previous elements = 25 if the last elemnet of row is exanded.
                        if (classList[Columns] == 6 && positionRow == 6) {
                            expandClass = 25;
                        }
                        var startIndex = (index - prevElement);
                        var previousElements = Array.from(target.parentNode.children).slice(startIndex, index);
                        previousElements.forEach((prev) => {
                            removeClass(prev);
                            prev.classList.add('col-sm-' + expandClass.toString());
                        });

                        var remain = (12 - updateColumns[Columns] + Columns);
                        if (remain > 0) {

                            var nextElements = Array.from(target.parentNode.children).slice(index + 1, index + 2);
                            nextElements.forEach((prev) => {
                                removeClass(prev);
                                prev.classList.add('col-sm-4');
                            });
                        }
                    }


                });
            });
        }
    };

    const removeClass = (target) => {
        target.classList.forEach((htmlClass) => {
            if (htmlClass.startsWith('col-sm')) {
                target.classList.remove(htmlClass);
            }
        });
    };

    const resetOtherClass = (target) => {
        var cards = Block.querySelectorAll(SELECTORS.block);
        cards.forEach((card) => {
            if (card != target) {
                removeClass(card);
                card.classList.add('col-sm-' + Columns.toString());
            }
        });
    };

    return {
        init: function (uniqueid, columnSize = 1) {
            uniqueID = uniqueid;
            Columns = parseInt(columnSize);
            expandDetails();
        }
    };
});
