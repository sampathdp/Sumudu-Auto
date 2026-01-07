/**
 * Autocomplete Helper
 * attached to an input field to show a dropdown of suggestions.
 *
 * Usage:
 * new Autocomplete({
 *    inputSelector: '#myInput',
 *    fetchData: function(term, callback) { ... },
 *    renderItem: function(item) { return '<div>' + item.name + '</div>'; },
 *    onSelect: function(item) { ... }
 * });
 */
class Autocomplete {
  constructor(options) {
    this.input = $(options.inputSelector);
    this.fetchData = options.fetchData;
    this.renderItem = options.renderItem || ((item) => `<div>${item}</div>`);
    this.onSelect = options.onSelect || (() => {});
    this.minChars = options.minChars || 1;
    this.debounceTime = options.debounceTime || 300;

    this.dropdownId = "autocomplete-" + Math.random().toString(36).substr(2, 9);
    this.container = null;
    this.timeout = null;

    this.init();
  }

  init() {
    // Create dropdown container
    this.container = $(
      `<div id="${this.dropdownId}" class="autocomplete-dropdown-menu"></div>`
    );
    this.container.css({
      display: "none",
      position: "absolute",
      "z-index": "1000",
      background: "#fff", // fallback
      border: "1px solid #1f2937", // dark border
      "border-radius": "0 0 8px 8px",
      "box-shadow": "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
      "max-height": "300px",
      "overflow-y": "auto",
      width: this.input.outerWidth() + "px",
      "background-color": "#1f2937", // Dark background like image
      color: "#fff",
    });

    $("body").append(this.container);

    // Input events
    this.input.on("input", () => {
      clearTimeout(this.timeout);
      const term = this.input.val().trim();

      if (term.length < this.minChars) {
        this.hide();
        return;
      }

      this.timeout = setTimeout(() => {
        this.fetchData(term, (items) => {
          this.render(items);
        });
      }, this.debounceTime);
    });

    this.input.on("focus", () => {
      const term = this.input.val().trim();
      if (term.length >= this.minChars) {
        this.fetchData(term, (items) => {
          this.render(items);
        });
      }
    });

    // Hide on click outside
    $(document).on("click", (e) => {
      if (
        !this.input.is(e.target) &&
        !this.container.is(e.target) &&
        this.container.has(e.target).length === 0
      ) {
        this.hide();
      }
    });

    // Update position on resize/scroll
    $(window).on("resize scroll", () => {
      if (this.container.is(":visible")) {
        this.updatePosition();
      }
    });
  }

  updatePosition() {
    const offset = this.input.offset();
    this.container.css({
      top: offset.top + this.input.outerHeight(),
      left: offset.left,
      width: this.input.outerWidth(),
    });
  }

  render(items) {
    this.container.empty();

    if (!items || items.length === 0) {
      const noResEl = $(
        `<div class="autocomplete-item p-2 text-primary fst-italic text-center" style="border-bottom: 1px solid #374151;">No matches found</div>`
      );
      this.container.append(noResEl);
      this.updatePosition();
      this.container.show();
      return;
    }

    items.forEach((item) => {
      const itemEl = $(
        `<div class="autocomplete-item p-2 border-bottom border-secondary" style="cursor: pointer; border-color: #374151 !important;"></div>`
      );
      itemEl.html(this.renderItem(item));

      itemEl.hover(
        function () {
          $(this).css("background-color", "#374151");
        }, // hover
        function () {
          $(this).css("background-color", "transparent");
        }
      );

      itemEl.on("click", () => {
        this.onSelect(item);
        this.hide();
      });

      this.container.append(itemEl);
    });

    this.updatePosition();
    this.container.show();
  }

  hide() {
    this.container.hide();
  }
}
