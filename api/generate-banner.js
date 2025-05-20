export default async function handler(req, res) {
    if (req.method !== 'POST') return res.status(405).end();
  
    const { prompt } = req.body;
  
    try {
      const response = await fetch("https://api.openai.com/v1/images/generations", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${process.env.OPENAI_API_KEY}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          prompt,
          n: 1,
          size: "1024x1024",
        }),
      });
  
      const data = await response.json();
      res.status(200).json(data);
    } catch (error) {
      console.error("Error:", error);
      res.status(500).json({ error: "Gagal menghasilkan desain" });
    }
  }
  