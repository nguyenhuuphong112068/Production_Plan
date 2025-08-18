import React, { useState } from "react";
import { InputGroup, Form, Button } from "react-bootstrap";

const CalendarSearchBox = ({ onSearch, onNext }) => {
  const [localText, setLocalText] = useState("");

  const handleSubmit = () => {
    if (onSearch) onSearch(localText);
  };

  return (
    <InputGroup size="sm">
      <Form.Control
        type="text"
        placeholder="Tìm sản phẩm..."
        value={localText}
        onChange={(e) => setLocalText(e.target.value)}
        onKeyDown={(e) => {if (e.key === "Enter") handleSubmit();}}
      />
      <Button variant="outline-secondary" onClick={handleSubmit}>
        <i className="fas fa-search"></i>
      </Button>
      {/* <Button variant="outline-primary" onClick={onNext}>
        Next
      </Button> */}
    </InputGroup>
  );
};

export default CalendarSearchBox;
