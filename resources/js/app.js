/**
 * resources/js/app.js
 * ------------------------------------------------------------
 * Orden recomendado:
 *  1.  Estilos base (Sass / CSS)
 *  2.  Bootstrap o inicialización del proyecto
 *  3.  Librerías Core (jQuery, Alpine, etc.)
 *  4.  Librerías UI / Componentes
 *  5.  Gráficos, mapas y calendarios
 *  6.  Formularios y validación
 *  7.  Scripts personalizados
 *  8.  Carga de assets (imágenes, fuentes, …)
 * ------------------------------------------------------------
 */

/* ----------------------------------------------------------------
 * 1. Estilos base
 * ---------------------------------------------------------------- */
import "../css/app.scss";

/* ----------------------------------------------------------------
 * 2. Bootstrap / inicialización de Laravel
 * ---------------------------------------------------------------- */
import "./bootstrap";

/* ----------------------------------------------------------------
 * 3. Librerías Core
 * ---------------------------------------------------------------- */
import jQuery from "jquery";
window.$ = window.jQuery = jQuery;

/* ----------------------------------------------------------------
 * 4. Librerías UI / Componentes
 * ---------------------------------------------------------------- */
// tw‑elements (Tailwind + Bootstrap components)
import "tw-elements";

// SimpleBar (scrollbars personalizados)
import SimpleBar from "simplebar";
// Fix: Changed SimpleBar CSS import path
import "simplebar/dist/simplebar.css";
window.SimpleBar = SimpleBar;

// Animate.css
import "animate.css";

// ResizeObserver polyfill (para navegadores antiguos)
import ResizeObserver from "resize-observer-polyfill";
window.ResizeObserver = ResizeObserver;

// Select2 (selects avanzados)
import "select2";
import "select2/dist/css/select2.min.css";

// Dragula (drag & drop)
import dragula from "dragula/dist/dragula";
import "dragula/dist/dragula.css";
window.dragula = dragula;

// Iconify (iconos)
import "iconify-icon";

// SweetAlert2 (alertas)
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
window.Swal = Swal;

// Tippy.js (tooltips)
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";
window.tippy = tippy;

/* ----------------------------------------------------------------
 * 5. Gráficos, mapas y calendarios
 * ---------------------------------------------------------------- */
// Leaflet (mapas)
import leaflet from "leaflet";
import "leaflet/dist/leaflet.css";
window.leaflet = leaflet;

// Chart.js
import * as Chart from "chart.js";
window.Chart = Chart;

// ApexCharts
import ApexCharts from "apexcharts";
window.ApexCharts = ApexCharts;

// FullCalendar
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";

window.FullCalendar = {
    Calendar,
    dayGridPlugin,
    timeGridPlugin,
    listPlugin,
    interactionPlugin,
};

/* ----------------------------------------------------------------
 * 6. Formularios y validación
 * ---------------------------------------------------------------- */
// Cleave.js (formateo de entradas)
import Cleave from "cleave.js";
window.Cleave = Cleave;

// jquery-validation (validador de formularios)
import "jquery-validation";

// Country‑select (select de países)
import "country-select-js";

// DataTables
import DataTable from "datatables.net-dt";
import "datatables.net-dt/css/jquery.dataTables.css";
window.DataTable = DataTable;

/* ----------------------------------------------------------------
 * 7. Scripts personalizados
 * ---------------------------------------------------------------- */
// Ejemplos de importación de scripts propios:
// import "./custom/calendar";
// import "./custom/chat";
// import "./custom/email";

/* ----------------------------------------------------------------
 * 8. Assets (imágenes, fuentes, …)
 * ---------------------------------------------------------------- */
import.meta.glob(["../images/**"]);
