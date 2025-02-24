/**
 * Archivo: resources/js/app.js
 */

// 1. Importar estilos principales (Sass/CSS)
import "../css/app.scss";

// 2. Bootstrap o inicialización del proyecto
import "./bootstrap";



// 3. Importar librerías
import jQuery from "jquery";
window.$ = jQuery;
window.jQuery = jQuery;

// Tw-elements
import "tw-elements";

// Importa Select2 y su CSS
import 'select2/dist/js/select2.min.js';
import 'select2/dist/css/select2.min.css';

// SimpleBar
import SimpleBar from "simplebar";
import "simplebar/dist/simplebar.css";
window.SimpleBar = SimpleBar;

// Animate CSS
import "animate.css";

// ResizeObserver
import ResizeObserver from "resize-observer-polyfill";
window.ResizeObserver = ResizeObserver;

// Leaflet
import leaflet from "leaflet";
import "leaflet/dist/leaflet.css";
window.leaflet = leaflet;

// FullCalendar
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";

window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.timeGridPlugin = timeGridPlugin;
window.listPlugin = listPlugin;


// Cleave
import Cleave from "cleave.js";
window.Cleave = Cleave;

// Chart.js y ApexCharts
import * as Chart from "chart.js";
window.Chart = Chart;
import ApexCharts from "apexcharts";
window.ApexCharts = ApexCharts;

// Country Select
import "country-select-js";

// Dragula
import dragula from "dragula/dist/dragula";
import "dragula/dist/dragula.css";
window.dragula = dragula;

// Iconify
import "iconify-icon";

// SweetAlert2
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
window.Swal = Swal;

// Tippy
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";
window.tippy = tippy;

// DataTables
import DataTable from "datatables.net-dt";
import "datatables.net-dt/css/jquery.dataTables.css";
window.DataTable = DataTable;

// jQuery Validation
import validate from "jquery-validation";
window.validate = validate;

// Cleave.js (Duplicado en código original, pero manteniendo la consistencia)
import cleave from "cleave.js";
window.cleave = cleave;

// 4. Importar scripts personalizados
//import "./calendar";
// import './custom/chat';
// import './custom/email';
// etc.

// 5. Cargar imágenes (si es necesario)
import.meta.glob(["../images/**"]);
