import React from "react";

const EventFontSizeInput = ({ fontSize, setFontSize, min = 8, max = 30 }) => {
  return (
    <div>
      <span>Size: </span>
      <input
        type="number"
        value={fontSize}
        min={min}
        max={max}
        onChange={(e) => {
          const value = Number(e.target.value);
          if (!isNaN(value)) setFontSize(Math.max(min, Math.min(max, value)));
        }}
        className="border rounded px-2 py-1 w-20 text-black"
      />
    </div>
  );
};

export default EventFontSizeInput;