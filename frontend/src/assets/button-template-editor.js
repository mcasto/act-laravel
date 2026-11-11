import {
  mdiCircle,
  mdiCodeBraces,
  mdiFormatColorText,
  mdiFormatSize,
} from "@quasar/extras/mdi-v7";

// q-editor config for the patron purchase-option (standard_buttons)
// templates edited on the admin Payment Methods page.

// q-editor only syncs its v-model on the contentEditable's native "input"
// event, so DOM mutations must go through execCommand (which dispatches
// that event) rather than raw Range/Node manipulation — otherwise the
// change is visible on screen but never reaches the Vue model, and a save
// right after silently reverts to whatever was there before.
const insertText = (text) => document.execCommand("insertText", false, text);

// Rich content pasted from Gmail/Word/etc. carries along font/color spans
// and can corrupt or truncate embedded {{ $placeholder }} text; force paste
// to plain text so templates stay predictable and placeholders survive.
//
// q-editor doesn't set inheritAttrs: false, so Vue's automatic attrs
// fallthrough attaches this same @paste listener to q-editor's outer
// wrapper div *in addition to* the inner contentEditable div it's meant
// for. Since "paste" bubbles, one paste fires the handler twice (inner,
// then outer) and inserts the text twice — stopPropagation keeps it from
// reaching that second, duplicate binding.
export const handlePlainTextPaste = (event) => {
  event.preventDefault();
  event.stopPropagation();
  insertText(event.clipboardData?.getData("text/plain") ?? "");
};

const insertPlaceholder = (text) => () => insertText(text);

// A small preset color palette (rather than a full color-picker popup) —
// these are plain {cmd, param} entries, so q-editor runs them the same way
// it runs its own built-in buttons (document.execCommand("foreColor", ...)),
// no custom handler needed.
const colorDefinitions = {
  colorBlack: { cmd: "foreColor", param: "#000000", tip: "Black", icon: mdiCircle, color: "black" },
  colorGrey: { cmd: "foreColor", param: "#666666", tip: "Grey", icon: mdiCircle, color: "grey-7" },
  colorRed: { cmd: "foreColor", param: "#d32f2f", tip: "Red", icon: mdiCircle, color: "red" },
  colorOrange: { cmd: "foreColor", param: "#f57c00", tip: "Orange", icon: mdiCircle, color: "orange" },
  colorGreen: { cmd: "foreColor", param: "#388e3c", tip: "Green", icon: mdiCircle, color: "green" },
  colorBlue: { cmd: "foreColor", param: "#1976d2", tip: "Blue", icon: mdiCircle, color: "blue" },
  colorPurple: { cmd: "foreColor", param: "#7b1fa2", tip: "Purple", icon: mdiCircle, color: "purple" },
};

// Placeholders available to the on-site popup templates (see
// resources/views/standard-buttons/*.blade.php on the backend), which are
// rendered with `param` (the price/amount) and `subject` (what the payment
// is for) passed in as variables.
export const templateDefinitions = {
  ...colorDefinitions,
  insertParam: {
    tip: "The price or amount, e.g. \"$25\" or \"$25 per ticket\"",
    label: "Amount — {{ $param }}",
    handler: insertPlaceholder("{{ $param }}"),
  },
  insertSubject: {
    tip: "What the payment is for, e.g. \"Support Us\" or \"you purchased Flex Tickets\"",
    label: "Description — {{ $subject }}",
    handler: insertPlaceholder("{{ $subject }}"),
  },
};

// Placeholders available to confirmation emails (see the `$confirmationData`
// array built in TicketSaleController@store), rendered via Blade::render
// before being spliced into purchase-confirmation/flex-confirmation blade views.
export const confirmationDefinitions = {
  ...colorDefinitions,
  insertName: {
    tip: "The patron's full name",
    label: "Patron Name — {{ $name }}",
    handler: insertPlaceholder("{{ $name }}"),
  },
  insertShowName: {
    tip: "The name of the show",
    label: "Show — {{ $show_name }}",
    handler: insertPlaceholder("{{ $show_name }}"),
  },
  insertNumTickets: {
    tip: "The number of tickets purchased",
    label: "# of Tickets — {{ $num_tickets }}",
    handler: insertPlaceholder("{{ $num_tickets }}"),
  },
  insertPerformanceDate: {
    tip: "The performance date, e.g. \"August 6, 2026\"",
    label: "Performance Date — {{ $performance_date }}",
    handler: insertPlaceholder("{{ $performance_date }}"),
  },
  insertPerformanceTime: {
    tip: "The performance start time, e.g. \"7:00 PM\"",
    label: "Performance Time — {{ $performance_time }}",
    handler: insertPlaceholder("{{ $performance_time }}"),
  },
};

const baseToolbar = [
  ["bold", "italic", "underline", "strike"],
  ["unordered", "ordered"],
  ["link", "code"],
  [
    {
      icon: mdiFormatSize,
      fixedIcon: true,
      list: "no-icons",
      options: ["size-1", "size-2", "size-3", "size-4", "size-5", "size-6", "size-7"],
    },
    {
      icon: mdiFormatColorText,
      fixedIcon: true,
      list: "only-icons",
      options: [
        "colorBlack",
        "colorGrey",
        "colorRed",
        "colorOrange",
        "colorGreen",
        "colorBlue",
        "colorPurple",
      ],
    },
  ],
  ["undo", "redo"],
];

export const templateToolbar = [
  ...baseToolbar,
  [
    {
      label: "Insert Parameter",
      icon: mdiCodeBraces,
      fixedLabel: true,
      list: "no-icons",
      options: ["insertParam", "insertSubject"],
    },
  ],
];

export const confirmationToolbar = [
  ...baseToolbar,
  [
    {
      label: "Insert Parameter",
      icon: mdiCodeBraces,
      fixedLabel: true,
      list: "no-icons",
      options: [
        "insertName",
        "insertShowName",
        "insertNumTickets",
        "insertPerformanceDate",
        "insertPerformanceTime",
      ],
    },
  ],
];
