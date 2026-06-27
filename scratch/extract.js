const fs = require('fs');

const content = fs.readFileSync('resources/js/Pages/FullCalender.jsx', 'utf8');
const startMatch = content.indexOf('const handleOptimizeSchedule = async () => {');
if (startMatch === -1) {
    console.log("Function not found");
    process.exit(1);
}

let openBraces = 0;
let endIndex = -1;
let started = false;

for (let i = startMatch; i < content.length; i++) {
    if (content[i] === '{') {
        openBraces++;
        started = true;
    } else if (content[i] === '}') {
        openBraces--;
        if (started && openBraces === 0) {
            endIndex = i;
            break;
        }
    }
}

if (endIndex !== -1) {
    const funcCode = content.substring(startMatch, endIndex + 1);
    fs.writeFileSync('scratch/extract.js', funcCode);
    console.log("Extracted to scratch/extract.js. Start:", startMatch, "End:", endIndex);
} else {
    console.log("Could not find matching brace");
}
