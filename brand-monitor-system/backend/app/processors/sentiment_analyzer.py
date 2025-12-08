from __future__ import annotations

import json
from typing import Dict, List

from anthropic import Anthropic

from ..core.config import settings


class SentimentAnalyzer:
    def __init__(self):
        self.client = Anthropic(api_key=settings.anthropic_api_key)

    def analyze_batch(self, mentions: List[Dict]) -> List[Dict]:
        """Analyze sentiment for a batch of mentions"""

        mentions_text = "\n\n".join(
            [
                (
                    f"MENTION {i + 1}:\n"
                    f"Title: {m.get('title', 'N/A')}\n"
                    f"Content: {m.get('content', '')[:500]}"
                )
                for i, m in enumerate(mentions)
            ]
        )

        prompt = f"""Analyze the sentiment of these brand mentions and return a JSON array with sentiment analysis for each mention.

{mentions_text}

For each mention, provide:
- sentiment: "positive", "negative", or "neutral"
- sentiment_score: number between -1.0 (very negative) and 1.0 (very positive)
- confidence_score: number between 0.0 and 1.0
- entities: list of people, organizations, products mentioned
- crisis_indicator: boolean - true if this indicates a potential PR crisis

Return ONLY valid JSON array, no other text."""

        response = self.client.messages.create(
            model="claude-sonnet-4-20250514",
            max_tokens=4000,
            messages=[{"role": "user", "content": prompt}],
        )

        results = json.loads(response.content[0].text)
        return results


analyzer = SentimentAnalyzer()
