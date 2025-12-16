from __future__ import annotations

from typing import List


def extract_entities(text: str) -> List[str]:
    """Placeholder entity extractor until NLP pipeline is implemented."""
    if not text:
        return []
    # naive keyword split, replace with spaCy or Claude structured output later
    return list({token.strip(".,!?") for token in text.split() if token.istitle()})
