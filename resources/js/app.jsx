
import 'primereact/resources/themes/lara-light-indigo/theme.css';
import 'primereact/resources/primereact.min.css';                
import 'primeicons/primeicons.css';    
import '../css/app.css';

import React from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import NoteModal from './Components/NoteModal';
import ScheduleTest from "./Pages/FullCalender.jsx";
import AssignmentCalender from './Pages/AssignmentCalender.jsx';



createRoot(document.getElementById("root")).render(
  <BrowserRouter basename="/Schedual">
    <Routes>
      <Route index element={<ScheduleTest />} />
      <Route path="assignment" element={<AssignmentCalender />} />
    </Routes>
  </BrowserRouter>
);

