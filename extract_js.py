import json
import re

log_path = r"C:\Users\QA2-Phongnh\.gemini\antigravity-ide\brain\829b3309-3a4f-45ee-87c8-f4d2e091efec\.system_generated\logs\transcript.jsonl"

for line in open(log_path, 'r', encoding='utf-8'):
    if 'multi_replace_file_content' in line:
        try:
            data = json.loads(line)
            tool_calls = data.get('tool_calls', [])
            for call in tool_calls:
                if call.get('name') == 'multi_replace_file_content':
                    args = call.get('args', {})
                    chunks_str = args.get('ReplacementChunks', '[]')
                    target = args.get('TargetFile', '')
                    if 'dataTable.blade.php' in target:
                        chunks = json.loads(chunks_str)
                        for chunk in chunks:
                            rep = chunk.get('ReplacementContent', '')
                            if "$('.btn-history').on('click'" in rep:
                                print(f"--- Found JS for {target} ---")
                                # Extract just the <script> part or the JS part
                                print(rep)
        except Exception as e:
            print("Error parsing line", e)
